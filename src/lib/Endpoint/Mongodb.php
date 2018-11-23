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
use Tubee\EndpointObject\EndpointObjectInterface;
use Tubee\Workflow\Factory as WorkflowFactory;

class Mongodb extends AbstractEndpoint
{
    /**
     * Kind.
     */
    public const KIND = 'MongodbEndpoint';

    /**
     * Collection.
     *
     * @var Collection
     */
    protected $collection;

    /**
     * Init endpoint.
     */
    public function __construct(string $name, string $type, Collection $collection, DataTypeInterface $datatype, WorkflowFactory $workflow, LoggerInterface $logger, array $resource = [])
    {
        $this->collection = $collection;
        parent::__construct($name, $type, $datatype, $workflow, $logger, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function getOne(array $object, array $attributes = []): EndpointObjectInterface
    {
        return $this->build($this->get($object, $attributes));
    }

    /**
     * {@inheritdoc}
     */
    public function exists(array $object): bool
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
    public function transformQuery(?array $query = null)
    {
        $result = null;
        if ($this->filter_all !== null) {
            $result = json_decode(stripslashes($this->filter_all), true);
        }

        if (!empty($query)) {
            if ($this->filter_all === null) {
                $result = $query;
            } else {
                $result = [
                    '$and' => [
                        json_decode(stripslashes($this->filter_all), true),
                        $query,
                    ],
                ];
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(?array $query = null): Generator
    {
        $i = 0;
        foreach ($this->collection->find($this->transformQuery($query)) as $data) {
            yield $this->build($data);
            ++$i;
        }

        return $i;
    }

    /**
     * {@inheritdoc}
     */
    public function create(AttributeMapInterface $map, array $object, bool $simulate = false): ?string
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
    public function change(AttributeMapInterface $map, array $diff, array $object, array $endpoint_object, bool $simulate = false): ?string
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
    public function delete(AttributeMapInterface $map, array $object, ?array $endpoint_object = null, bool $simulate = false): bool
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
     */
    protected function get(array $object, array $attributes = []): array
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
}
