<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use Generator;
use InvalidArgumentException;
use MongoDB\Collection;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\DataType\DataTypeInterface;

class Mongodb extends AbstractEndpoint
{
    /**
     * Collection.
     *
     * @var Collection
     */
    protected $collection;

    /**
     * Init endpoint.
     *
     * @param string            $name
     * @param string            $type
     * @param Collection        $collection
     * @param DataTypeInterface $datatype
     * @param Logger            $logger
     * @param iterable          $config
     */
    public function __construct(string $name, string $type, Collection $collection, DataTypeInterface $datatype, LoggerInterface $logger, Iterable $config)
    {
        $this->collection = $collection;
        parent::__construct($name, $type, $datatype, $logger, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function getOne(Iterable $object, Iterable $attributes = []): Iterable
    {
        return $this->get($object, $attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(Iterable $object): bool
    {
        try {
            $this->get($object);

            return true;
        } catch (Exception\ObjectMultipleFound $e) {
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAll($filter = []): Generator
    {
        $filter = $this->buildFilterAll((array) $filter['$match']);

        foreach ($this->collection->find($filter) as $data) {
            yield $data;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function create(AttributeMapInterface $map, Iterable $object, bool $simulate = false): ?string
    {
        $this->logger->debug('create new mongodb object on endpoint ['.$this->name.'] with values [{values}]', [
            'category' => get_class($this),
            'values' => $object,
        ]);

        if ($simulate === false) {
            return (string) $this->collection->insertOne($object);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function change(AttributeMapInterface $map, Iterable $diff, Iterable $object, Iterable $endpoint_object, bool $simulate = false): ?string
    {
        $filter = $this->getFilterOne($object);
        $this->logger->info('update mongodb object on endpoint ['.$this->getIdentifier().']', [
            'category' => get_class($this),
        ]);

        if ($simulate === false) {
            $this->collection->updateOne($filter, $diff);
        }

        return (string) $endpoint_object['_id'];
    }

    /**
     * {@inheritdoc}
     */
    public function getDiff(AttributeMapInterface $map, array $diff): array
    {
        $result = [];
        foreach ($diff as $attribute => $update) {
            switch ($update['action']) {
                case AttributeMapInterface::ACTION_REPLACE:
                    $result['$set'][$attribute] = $update['value'];

                break;
                case AttributeMapInterface::ACTION_REMOVE:
                    $result['$unset'][$attribute] = true;

                break;
                case AttributeMapInterface::ACTION_ADD:
                    $result['$addToSet'][$attribute] = $update['value'];

                break;
                default:
                    throw new InvalidArgumentException('unknown diff action '.$update['action'].' given');
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(AttributeMapInterface $map, Iterable $object, ?Iterable $endpoint_object = null, bool $simulate = false): bool
    {
        $filter = $this->getFilterOne($object);

        $this->logger->info('delete mongodb object on endpoint ['.$this->name.'] with filter ['.json_encode($filter).']', [
            'category' => get_class($this),
        ]);

        if ($simulate === false) {
            $this->collection->deleteOne($filter);
        }

        return true;
    }

    /**
     * Get existing object.
     *
     * @param iterable $object
     * @param iterable $attributes
     *
     * @return array
     */
    protected function get(Iterable $object, Iterable $attributes = []): array
    {
        $result = [];
        $filter = $this->getFilterOne($object);

        foreach ($this->collection->find($filter) as $data) {
            $result[] = $data;
        }

        if (count($result) > 1) {
            throw new Exception\ObjectMultipleFound('found more than one object with filter '.json_encode($filter));
        }
        if (count($result) === 0) {
            throw new Exception\ObjectNotFound('no object found with filter '.json_encode($filter));
        }

        return (array) array_shift($result);
    }

    /**
     * Build filter all.
     *
     * @param string $filter
     *
     * @return string
     */
    protected function buildFilterAll($filter): array
    {
        if ($filter !== null && $this->filter_all !== null) {
            return array_merge($filter, $this->filter_all);
        }
        if ($filter !== null) {
            return $filter;
        }
        if ($this->filter_all !== null) {
            return $this->filter_all;
        }

        return null;
    }
}
