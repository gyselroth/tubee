<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use Generator;
use InvalidArgumentException;
use MongoDB\Collection;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\EndpointObject\EndpointObjectInterface;
use Tubee\Workflow\Factory as WorkflowFactory;

class Mongodb extends AbstractEndpoint
{
    use LoggerTrait;

    /**
     * Kind.
     */
    public const KIND = 'MongodbEndpoint';

    /**
     * Collection.
     *
     * @var Collection
     */
    protected $pool;

    /**
     * Init endpoint.
     */
    public function __construct(string $name, string $type, Collection $pool, CollectionInterface $collection, WorkflowFactory $workflow, LoggerInterface $logger, array $resource = [])
    {
        $this->pool = $pool;
        parent::__construct($name, $type, $collection, $workflow, $logger, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function getOne(array $object, array $attributes = []): EndpointObjectInterface
    {
        $result = [];
        $filter = $this->getFilterOne($object);
        $this->logGetOne($filter);

        foreach ($this->pool->find($filter) as $data) {
            $result[] = $data;
        }

        if (count($result) > 1) {
            throw new Exception\ObjectMultipleFound('found more than one object with filter '.json_encode($filter));
        }
        if (count($result) === 0) {
            throw new Exception\ObjectNotFound('no object found with filter '.json_encode($filter));
        }

        return $this->build(array_shift($result), $filter);
    }

    /**
     * {@inheritdoc}
     */
    public function transformQuery(?array $query = null)
    {
        if ($this->filter_all !== null && empty($query)) {
            return $this->getFilterAll();
        }
        if (!empty($query)) {
            if ($this->filter_all === null) {
                return $query;
            }

            return [
                    '$and' => [
                        $this->getFilterAll(),
                        $query,
                    ],
                ];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(?array $query = null): Generator
    {
        $filter = $this->transformQuery($query);
        $this->logGetAll($filter);

        $i = 0;
        foreach ($this->pool->find($filter) as $data) {
            yield $this->build(json_decode(json_encode($data), true));
            ++$i;
        }

        return $i;
    }

    /**
     * {@inheritdoc}
     */
    public function create(AttributeMapInterface $map, array $object, bool $simulate = false): ?string
    {
        $this->logCreate($object);

        if ($simulate === false) {
            return (string) $this->pool->insertOne($object);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function change(AttributeMapInterface $map, array $diff, array $object, EndpointObjectInterface $endpoint_object, bool $simulate = false): ?string
    {
        $this->logChange($endpoint_object->getFilter(), $diff);

        if ($simulate === false) {
            $this->pool->updateOne($endpoint_object->getFilter(), $diff);
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
    public function delete(AttributeMapInterface $map, array $object, EndpointObjectInterface $endpoint_object, bool $simulate = false): bool
    {
        $this->logDelete($endpoint_object->getFilter());
        if ($simulate === false) {
            $this->pool->deleteOne($endpoint_object->getFilter());
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

        foreach ($this->pool->find($filter) as $data) {
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
