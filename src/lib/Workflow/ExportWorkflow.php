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
        $attributes['relations'] = $object->getResolvedRelationsAsArray();
        if ($this->checkCondition($attributes) === false) {
            return false;
        }

        $exists = false;
        $result = null;

        try {
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
                    $result = $this->ensureAbsent($object, $exists, $map, $simulate);
                    $exists = false;

                break;
                case WorkflowInterface::ENSURE_EXISTS:
                    $result = $this->ensureExists($object, $map, $ts, $simulate);
                    $exists = true;

                break;
                default:
                case WorkflowInterface::ENSURE_LAST:
                    $result = $this->ensureLast($object, $exists, $map, $ts, $simulate);
            }
        } catch (\Exception $e) {
            $this->updateObject($object, $simulate, $ts, $result, [
                'garbage' => !$exists,
                'exception' => $e,
                'success' => false,
            ]);

            throw $e;
        }

        $this->updateObject($object, $simulate, $ts, $result, [
            'garbage' => !$exists,
            'exception' => null,
            'success' => true,
        ]);

        return true;
    }

    /**
     * Update object on endpoint.
     */
    protected function ensureLast(DataObjectInterface $object, EndpointObjectInterface $exists, array $map, UTCDateTimeInterface $ts, bool $simulate = false): ?string
    {
        $this->logger->info('change object on endpoint ['.$this->endpoint->getIdentifier().']', [
            'category' => get_class($this),
        ]);

        $diff = $this->attribute_map->getDiff($map, $exists->getData());

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

            return $this->endpoint->change($this->attribute_map, $diff, $map, $exists->getData(), $simulate);
        }
        $this->logger->debug('object on endpoint ['.$this->endpoint->getIdentifier().'] is already up2date', [
                'category' => get_class($this),
            ]);

        if (isset($exists->getData()[$this->endpoint->getResourceIdentifier()])) {
            return  $exists->getData()[$this->endpoint->getResourceIdentifier()];
        }

        return null;
    }

    /**
     * Create object on endpoint.
     */
    protected function ensureExists(DataObjectInterface $object, array $map, UTCDateTimeInterface $ts, bool $simulate = false): ?string
    {
        $this->logger->info('create new object {object} on endpoint ['.$this->endpoint->getIdentifier().']', [
            'category' => get_class($this),
        ]);

        return $this->endpoint->create($this->attribute_map, $map, $simulate);
    }

    /**
     * Remove object from endpoint.
     */
    protected function ensureAbsent(DataObjectInterface $object, EndpointObjectInterface $exists, array $map, bool $simulate = false): ?string
    {
        $this->logger->info('delete existing object from endpoint ['.$this->endpoint->getIdentifier().']', [
            'category' => get_class($this),
        ]);

        $this->endpoint->delete($this->attribute_map, $map, $exists->getData(), $simulate);

        return null;
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
                'resource' => json_encode($exists->getData()),
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
