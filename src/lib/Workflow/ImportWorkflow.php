<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Workflow;

use MongoDB\BSON\UTCDateTimeInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\DataObject\DataObjectInterface;
use Tubee\DataObject\Exception as DataObjectException;
use Tubee\EndpointObject\EndpointObjectInterface;
use Tubee\Helper;
use Tubee\Workflow;

class ImportWorkflow extends Workflow
{
    /**
     * {@inheritdoc}
     */
    public function cleanup(DataObjectInterface $object, UTCDateTimeInterface $ts, bool $simulate = false): bool
    {
        $attributes = $object->toArray();
        if ($this->checkCondition($attributes) === false) {
            return false;
        }

        $attributes = Helper::associativeArrayToPath($attributes);
        $map = $this->attribute_map->map($attributes, $ts);
        $this->logger->info('mapped object attributes [{map}] for cleanup', [
            'category' => get_class($this),
            'map' => array_keys($map),
        ]);

        switch ($this->ensure) {
            case WorkflowInterface::ENSURE_ABSENT:
                return $this->endpoint->getCollection()->deleteObject($object->getId(), $simulate);

            break;
            default:
            case WorkflowInterface::ENSURE_LAST:
                $resource = Map::map($this->attribute_map, $map, ['data' => $object->getData()], $ts);
                $object->getCollection()->changeObject($object, $resource, $simulate);
                $this->importRelations($object, $map, $simulate);

                return true;

            break;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function import(CollectionInterface $collection, EndpointObjectInterface $object, UTCDateTimeInterface $ts, bool $simulate = false): bool
    {
        $object = $object->getData();
        if ($this->checkCondition($object) === false) {
            return false;
        }

        $map = $this->attribute_map->map($object, $ts);
        $this->logger->info('mapped object attributes [{map}] for import', [
            'category' => get_class($this),
            'map' => array_keys($map),
        ]);

        $exists = $this->getImportObject($collection, $map, $object, $ts);

        if ($exists === null) {
            $this->logger->info('found no existing data object for given import attributes', [
                'category' => get_class($this),
            ]);
        } else {
            $this->logger->info('identified existing data object ['.$exists->getId().'] for import', [
                'category' => get_class($this),
            ]);
        }

        $ensure = $this->ensure;
        if ($exists !== null && $this->ensure === WorkflowInterface::ENSURE_EXISTS) {
            return false;
        }
        if ($exists === null && $this->ensure === WorkflowInterface::ENSURE_LAST) {
            $ensure = WorkflowInterface::ENSURE_EXISTS;
        }

        switch ($ensure) {
            case WorkflowInterface::ENSURE_ABSENT:
                $collection->deleteObject($exists->getId(), $simulate);

                return true;

            break;
            case WorkflowInterface::ENSURE_EXISTS:
                $endpoints = [
                    $this->endpoint->getName() => [
                        'last_sync' => $ts,
                        'garbage' => false,
                    ],
                ];

                $id = $collection->createObject(Helper::pathArrayToAssociative($this->removeMapAttributes($map)), $simulate, $endpoints);
                $this->importRelations($collection->getObject(['_id' => $id]), $map, $simulate, $endpoints);

                return true;

            break;
            default:
            case WorkflowInterface::ENSURE_LAST:
                $object = Map::map($this->attribute_map, $this->removeMapAttributes($map), ['data' => $exists->getData()], $ts);
                $endpoints = [
                    $this->endpoint->getName() => [
                        'last_sync' => $ts,
                        'garbage' => false,
                    ],
                ];

                $collection->changeObject($exists, $object, $simulate, $endpoints);
                $this->importRelations($exists, $map, $simulate, $endpoints);

                return true;

            break;
        }

        return false;
    }

    /**
     * Remove map attributes (if skip=true).
     */
    protected function removeMapAttributes(array $data): array
    {
        foreach ($this->attribute_map->getMap() as $definition) {
            if ($definition['skip'] === true) {
                $this->logger->debug('do not store attribute ['.$definition['name'].']', [
                    'category' => get_class($this),
                ]);

                unset($data[$definition['name']]);
            }
        }

        return $data;
    }

    /**
     * Create object relations.
     */
    protected function importRelations(DataObjectInterface $object, array $data, bool $simulate, array $endpoints = []): bool
    {
        $this->logger->debug('find relationships to be imported for object ['.$object->getId().']', [
            'category' => get_class($this),
        ]);

        foreach ($this->attribute_map->getMap() as $definition) {
            if (!isset($definition['map'])) {
                continue;
            }

            if (!isset($data[$definition['name']])) {
                $this->logger->debug('relation attribute ['.$definition['map']['collection'].':'.$definition['map']['to'].'] not found in mapped data object', [
                    'category' => get_class($this),
                ]);

                continue;
            }

            $this->logger->debug('find related object from ['.$object->getId().'] to ['.$definition['map']['collection'].':'.$definition['map']['to'].'] => ['.$data[$definition['name']].']', [
                'category' => get_class($this),
            ]);

            $namespace = $this->endpoint->getCollection()->getResourceNamespace();
            $collection = $namespace->getCollection($definition['map']['collection']);
            $relative = $collection->getObject([
                $definition['map']['to'] => $data[$definition['name']],
            ]);

            $this->logger->debug('ensure relation state ['.$definition['map']['ensure'].'] for relation to ['.$relative->getId().']', [
                'category' => get_class($this),
            ]);

            switch ($definition['map']['ensure']) {
                case WorkflowInterface::ENSURE_EXISTS:
                case WorkflowInterface::ENSURE_LAST:
                    $context = array_intersect_key($data, array_flip($definition['map']['context']));
                    $namespace = $this->endpoint->getCollection()->getResourceNamespace()->getName();
                    $collection = $this->endpoint->getCollection()->getName();
                    $ep = $this->endpoint->getName();

                    $endpoints = [
                        join('/', [$namespace, $collection, $ep]) => $endpoints[$ep],
                    ];

                    $object->createOrUpdateRelation($relative, $context, $simulate, $endpoints);

                break;
                default:
            }
        }

        return true;
    }

    /**
     * Get import object.
     */
    protected function getImportObject(CollectionInterface $collection, array $map, array $object, UTCDateTimeInterface $ts): ?DataObjectInterface
    {
        $filter = array_intersect_key($map, array_flip($this->endpoint->getImport()));
        //TODO: debug import line here

        if (empty($filter) || count($filter) !== count($this->endpoint->getImport())) {
            throw new Exception\ImportConditionNotMet('import condition attributes are not available from mapping');
        }

        try {
            $exists = $collection->getObject($filter, false);
        } catch (DataObjectException\MultipleFound $e) {
            throw $e;
        } catch (DataObjectException\NotFound $e) {
            return null;
        }

        /*$endpoints = $exists->getEndpoints();

        if ($exists !== false && isset($endpoints[$this->endpoint->getName()])
        && $endpoints[$this->endpoint->getName()]['last_sync']->toDateTime() >= $ts->toDateTime()) {
            throw new Exception\ImportConditionNotMet('import filter matched multiple source objects');
        }*/

        return $exists;
    }
}
