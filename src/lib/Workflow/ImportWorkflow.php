<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2022 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Workflow;

use MongoDB\BSON\UTCDateTimeInterface;
use MongoDB\Collection;
use Tubee\Async\Sync;
use Tubee\Collection\CollectionInterface;
use Tubee\DataObject\DataObjectInterface;
use Tubee\DataObject\Exception as DataObjectException;
use Tubee\Endpoint\EndpointInterface;
use Tubee\EndpointObject\EndpointObjectInterface;
use Tubee\Helper;
use Tubee\ResourceNamespace\ResourceNamespaceInterface;
use Tubee\Workflow;

class ImportWorkflow extends Workflow
{
    /**
     * {@inheritdoc}
     */
    public function cleanup(DataObjectInterface $object, Sync $process, bool $simulate = false): bool
    {
        $attributes = $object->toArray();
        if ($this->checkCondition($attributes) === false) {
            return false;
        }

        $attributes = Helper::associativeArrayToPath($attributes);
        $map = $this->attribute_map->map($attributes, $process->getTimestamp());
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
                $resource = Map::map($this->attribute_map, $map, ['data' => $object->getData()], $process->getTimestamp());
                $object->getCollection()->changeObject($object, $resource, $simulate, [
                    'name' => $this->endpoint->getName(),
                    'last_garbage_sync' => $process->getTimestamp(),
                    'process' => $process->getId(),
                    'workflow' => $this->getName(),
                    'success' => true,
                ]);

                $this->importRelations($object, $map, $simulate);

                return true;

            break;
        }

        return false;
    }

    public function relationCleanup(Collection $collection, $relation, Sync $process, ResourceNamespaceInterface $namespace, EndpointInterface $endpoint, $workflow, bool $simulate = false): bool
    {
        if ($this->checkCondition($relation) === false) {
            return false;
        }

        $relationObject = $this->relation_factory->getOne($namespace, $relation['name']);
        $dataObject = $relationObject->getDataObjectByRelation($relationObject, $endpoint->getCollection());
        $dataObject->getCollection()->changeObject($dataObject, ['data' => $dataObject->getData()], $simulate, [
            'name' => $this->endpoint->getName(),
            'last_garbage_sync' => $process->getTimestamp(),
            'process' => $process->getId(),
            'workflow' => $this->getName(),
            'success' => true,
        ]);

        foreach ($workflow->getAttributeMap()->getMap() as $attr) {
            if (isset($attr['map']) && $attr['map']['ensure'] === 'absent') {
                $this->relation_factory->deleteOne($relationObject, $simulate);

                return true;
            }
        }

        $co = $endpoint->getCollection()->getName();
        $endpoint = $endpoint->getName();
        $key = join('/', [$namespace->getName(), $co, $endpoint]);

        $attributes = Helper::associativeArrayToPath($relation);
        $map = $this->attribute_map->map($attributes, $process->getTimestamp());

        $this->logger->info('mapped object attributes [{map}] for cleanup', [
            'category' => get_class($this),
            'map' => array_keys($map),
        ]);

        $update = (array) Map::map($this->attribute_map, $map, ['data' => $relationObject->getData()], $process->getTimestamp());
        $endpointData = $relationObject->toArray()['endpoints'][$key];

        $update['endpoints'][$key] = [
            'name' => $endpoint,
            'last_sync' => $endpointData['last_sync'],
            'last_successful_sync' => $endpointData['last_successful_sync'],
            'last_garbage_sync' => $process->getTimestamp(),
            'process' => $process->getId(),
            'workflow' => $this->getName(),
            'success' => true,
            'garbage' => true,
        ];

        return $this->resource_factory->updateIn($collection, $relationObject, $update, $simulate);
    }

    /**
     * {@inheritdoc}
     */
    public function import(CollectionInterface $collection, EndpointObjectInterface $object, Sync $process, bool $simulate = false): bool
    {
        $object = $object->getData();
        if ($this->checkCondition($object) === false) {
            return false;
        }

        $map = $this->attribute_map->map($object, $process->getTimestamp());
        $this->logger->info('mapped object attributes [{map}] for import', [
            'category' => get_class($this),
            'map' => array_keys($map),
        ]);

        $exists = $this->getImportObject($collection, $map, $object, $process->getTimestamp());

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
                $endpoint = [
                    'name' => $this->endpoint->getName(),
                    'last_sync' => $process->getTimestamp(),
                    'last_successful_sync' => $process->getTimestamp(),
                    'process' => $process->getId(),
                    'workflow' => $this->getName(),
                    'success' => true,
                    'garbage' => false,
                ];

                if (isset($object[$this->endpoint->getResourceIdentifier()])) {
                    $endpoint['result'] = $object[$this->endpoint->getResourceIdentifier()];
                }

                $id = $collection->createObject(Helper::pathArrayToAssociative($this->removeMapAttributes($map)), $simulate, $endpoint);
                $this->importRelations($collection->getObject(['_id' => $id]), $map, $simulate, $endpoint);

                return true;

            break;
            default:
            case WorkflowInterface::ENSURE_LAST:
                $mapped = Map::map($this->attribute_map, $map, ['data' => $exists->getData()], $process->getTimestamp());
                $endpoint = [
                    'name' => $this->endpoint->getName(),
                    'last_sync' => $process->getTimestamp(),
                    'last_successful_sync' => $process->getTimestamp(),
                    'process' => $process->getId(),
                    'workflow' => $this->getName(),
                    'success' => true,
                    'garbage' => false,
                ];

                if (isset($object[$this->endpoint->getResourceIdentifier()])) {
                    $endpoint['result'] = $object[$this->endpoint->getResourceIdentifier()];
                }

                $this->importRelations($exists, $map, $simulate, $endpoint);
                $exist_ep = $exists->getEndpoints();

                if (isset($exist_ep[$this->endpoint->getName()])
                    && $exist_ep[$this->endpoint->getName()]['last_sync']->toDateTime() >= $process->getTimestamp()->toDateTime()) {
                    $this->logger->warning('source object with given import filter is not unique (multiple data objects found), skip update resource', [
                        'category' => get_class($this),
                    ]);

                    return true;
                }

                $collection->changeObject($exists, $mapped, $simulate, $endpoint);

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
    protected function importRelations(DataObjectInterface $object, array $data, bool $simulate, array $endpoint = []): bool
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

            $this->logger->debug('find related object from ['.$object->getId().'] to ['.$definition['map']['collection'].':'.$definition['map']['to'].'] => [{value}]', [
                'category' => get_class($this),
                'value' => $data[$definition['name']],
            ]);

            $namespace = $this->endpoint->getCollection()->getResourceNamespace();
            $collection = $namespace->getCollection($definition['map']['collection']);
            $relatives = $collection->getObjects([
                $definition['map']['to'] => ['$in' => (array) $data[$definition['name']]],
            ]);

            $identifiers = [];
            foreach ($definition['map']['identifiers'] ?? [] as $attribute) {
                if (array_key_exists($attribute, $data)) {
                    $identifiers[$attribute] = $data[$attribute];
                }
            }

            foreach ($relatives as $relative) {
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

                        $list = [
                            join('/', [$namespace, $collection, $ep]) => $endpoint,
                        ];

                        $object->createOrUpdateRelation($relative, $identifiers, $context, $simulate, $list);

                    break;
                    default:
                }
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
        $this->logger->debug('try to match source object in collection with [{filter}]', [
            'filter' => json_encode($filter),
        ]);

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

        return $exists;
    }
}
