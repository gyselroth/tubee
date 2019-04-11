<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Workflow;

use Generator;
use MongoDB\BSON\UTCDateTimeInterface;
use Tubee\DataObject\DataObjectInterface;
use Tubee\Endpoint\Exception as EndpointException;
use Tubee\EndpointObject\EndpointObjectInterface;
use Tubee\Helper;
use Tubee\Workflow;

class ExportWorkflow extends Workflow
{
    /**
     * {@inheritdoc}
     */
    public function export(DataObjectInterface $object, UTCDateTimeInterface $ts, bool $simulate = false): bool
    {
        $attributes = $object->toArray();
        $attributes['relations'] = iterator_to_array($this->getRelations($object));
        if ($this->checkCondition($attributes) === false) {
            return false;
        }

        $map = $this->attribute_map->map($attributes, $ts);
        $this->logger->info('mapped object attributes [{map}] for write', [
            'category' => get_class($this),
            'map' => array_keys($map),
        ]);

        $exists = $this->getExportObject([
            'map' => $map,
            'object' => $attributes,
        ]);

        $map = Helper::pathArrayToAssociative($map);
        $ensure = $this->ensure;

        if ($exists === null && $this->ensure === WorkflowInterface::ENSURE_ABSENT) {
            $this->logger->info('skip object which is already absent from endpoint ['.$this->endpoint->getIdentifier().']', [
                'category' => get_class($this),
            ]);

            return true;
        }
        if ($exists !== null && $this->ensure === WorkflowInterface::ENSURE_EXISTS) {
            return false;
        }

        if ($exists === null && $this->ensure === WorkflowInterface::ENSURE_LAST) {
            $ensure = WorkflowInterface::ENSURE_EXISTS;
        }

        switch ($ensure) {
            case WorkflowInterface::ENSURE_ABSENT:
                return $this->ensureAbsent($exists, $map, $simulate);
            case WorkflowInterface::ENSURE_EXISTS:
                return $this->ensureExists($object, $map, $ts, $simulate);
            default:
            case WorkflowInterface::ENSURE_LAST:
                return $this->ensureLast($object, $exists, $map, $ts, $simulate);
        }

        return false;
    }

    /**
     * Update object on endpoint.
     */
    protected function ensureLast(DataObjectInterface $object, EndpointObjectInterface $exists, array $map, UTCDateTimeInterface $ts, bool $simulate = false): bool
    {
        $this->logger->info('change object on endpoint ['.$this->endpoint->getIdentifier().']', [
            'category' => get_class($this),
        ]);

        $diff = $this->attribute_map->getDiff($map, $exists->getData());

        $endpoints = [$this->endpoint->getName() => [
            'last_sync' => $ts,
            'garbage' => false,
        ]];

        if (count($diff) > 0) {
            $this->logger->info('update object on endpoint ['.$this->endpoint->getIdentifier().'] with attributes [{attributes}]', [
                'category' => get_class($this),
                'attributes' => $diff,
            ]);

            $diff = $this->endpoint->getDiff($this->attribute_map, $diff);

            $this->logger->debug('execute diff [{diff}] on endpoint ['.$this->endpoint->getIdentifier().']', [
                'category' => get_class($this),
                'diff' => $diff,
            ]);

            $result = $this->endpoint->change($this->attribute_map, $diff, $map, $exists->getData(), $simulate);

            if ($result !== null) {
                $endpoints[$this->endpoint->getName()]['result'] = $result;
            }
        } else {
            $this->logger->debug('object on endpoint ['.$this->endpoint->getIdentifier().'] is already up2date', [
                'category' => get_class($this),
            ]);
        }

        if (!isset($endpoints[$this->endpoint->getName()]['result'])) {
            if (isset($exists->getData()[$this->endpoint->getResourceIdentifier()])) {
                $endpoints[$this->endpoint->getName()]['result'] = $exists->getData()[$this->endpoint->getResourceIdentifier()];
            } else {
                $endpoints[$this->endpoint->getName()]['result'] = null;
            }
        }

        $this->endpoint->getCollection()->changeObject($object, $object->toArray(), $simulate, $endpoints);

        return true;
    }

    /**
     * Create object on endpoint.
     */
    protected function ensureExists(DataObjectInterface $object, array $map, UTCDateTimeInterface $ts, bool $simulate = false)
    {
        $this->logger->info('create new object {object} on endpoint ['.$this->endpoint->getIdentifier().']', [
            'category' => get_class($this),
        ]);

        $result = $this->endpoint->create($this->attribute_map, $map, $simulate);

        $endpoints = [
            $this->endpoint->getName() => [
                'last_sync' => $ts,
                'result' => $result,
                'garbage' => false,
            ],
        ];

        $this->endpoint->getCollection()->changeObject($object, $object->toArray(), $simulate, $endpoints);

        return true;
    }

    /**
     * Remove object from endpoint.
     */
    protected function ensureAbsent(EndpointObjectInterface $exists, array $map, bool $simulate = false)
    {
        $this->logger->info('delete existing object from endpoint ['.$this->endpoint->getIdentifier().']', [
            'category' => get_class($this),
        ]);

        $this->endpoint->delete($this->attribute_map, $map, $exists->getData(), $simulate);

        return true;
    }

    /**
     * Transform relations to array.
     */
    protected function getRelations(DataObjectInterface $object): Generator
    {
        foreach ($object->getRelations() as $relation) {
            $resource = $relation->toArray();
            $resource['object'] = $relation->getDataObject()->toArray();
            yield $resource;
        }
    }

    /**
     * Get export object.
     */
    protected function getExportObject(array $map): ?EndpointObjectInterface
    {
        try {
            if ($this->endpoint->flushRequired()) {
                return null;
            }

            $exists = $this->endpoint->getOne($map, $this->attribute_map->getAttributes());

            $this->logger->debug('found existing object {object} on destination endpoint with provided filter_one', [
                'category' => get_class($this),
                'object' => $exists->getData(),
            ]);

            return $exists;
        } catch (EndpointException\ObjectMultipleFound $e) {
            throw $e;
        } catch (EndpointException\ObjectNotFound $e) {
            $this->logger->debug('object does not exists yet on destination endpoint', [
                'category' => get_class($this),
                'exception' => $e,
            ]);
        } catch (EndpointException\AttributeNotResolvable $e) {
            $this->logger->debug('object filter can not be resolved, leading to non existing object', [
                'category' => get_class($this),
                'exception' => $e,
            ]);
        }

        return null;
    }
}
