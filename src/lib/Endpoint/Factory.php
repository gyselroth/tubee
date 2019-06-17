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
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\ObjectIdInterface;
use MongoDB\Database;
use Psr\Log\LoggerInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Helper;
use Tubee\Resource\Factory as ResourceFactory;
use Tubee\Secret\Factory as SecretFactory;
use Tubee\Workflow\Factory as WorkflowFactory;

class Factory
{
    /**
     * Collection name.
     */
    public const COLLECTION_NAME = 'endpoints';

    /**
     * Database.
     *
     * @var Database
     */
    protected $db;

    /**
     * Resource factory.
     *
     * @var ResourceFactory
     */
    protected $resource_factory;

    /**
     * Factory.
     *
     * @var WorkflowFactory
     */
    protected $workflow_factory;

    /**
     * Secret factory.
     *
     * @var SecretFactory
     */
    protected $secret_factory;

    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Initialize.
     */
    public function __construct(Database $db, ResourceFactory $resource_factory, WorkflowFactory $workflow_factory, SecretFactory $secret_factory, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->resource_factory = $resource_factory;
        $this->workflow_factory = $workflow_factory;
        $this->secret_factory = $secret_factory;
        $this->logger = $logger;
    }

    /**
     * Has endpoint.
     */
    public function has(CollectionInterface $collection, string $name): bool
    {
        return $this->db->{self::COLLECTION_NAME}->count([
            'name' => $name,
            'namespace' => $collection->getResourceNamespace()->getName(),
            'collection' => $collection->getName(),
        ]) > 0;
    }

    /**
     * Get all.
     */
    public function getAll(CollectionInterface $collection, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        $filter = $this->prepareQuery($collection, $query);
        $that = $this;

        return $this->resource_factory->getAllFrom($this->db->{self::COLLECTION_NAME}, $filter, $offset, $limit, $sort, function (array $resource) use ($collection, $that) {
            return $that->build($resource, $collection);
        });
    }

    /**
     * Get one.
     */
    public function getOne(CollectionInterface $collection, string $name): EndpointInterface
    {
        $result = $this->db->{self::COLLECTION_NAME}->findOne([
            'name' => $name,
            'namespace' => $collection->getResourceNamespace()->getName(),
            'collection' => $collection->getName(),
        ], [
            'projection' => ['history' => 0],
        ]);

        if ($result === null) {
            throw new Exception\NotFound('endpoint '.$name.' is not registered');
        }

        return $this->build($result, $collection);
    }

    /**
     * Delete by name.
     */
    public function deleteOne(CollectionInterface $collection, string $name): bool
    {
        $resource = $this->getOne($collection, $name);

        return $this->resource_factory->deleteFrom($this->db->{self::COLLECTION_NAME}, $resource->getId());
    }

    /**
     * Add.
     */
    public function add(CollectionInterface $collection, array $resource): ObjectIdInterface
    {
        $resource = $this->secret_factory->resolve($collection->getResourceNamespace(), $resource);
        $resource = $this->resource_factory->validate($resource);
        $resource = Validator::validate($resource);

        foreach ($resource['secrets'] as $secret) {
            $resource = Helper::deleteArrayValue($resource, $secret['to']);
        }

        if ($this->has($collection, $resource['name'])) {
            throw new Exception\NotUnique('endpoint '.$resource['name'].' does already exists');
        }

        $resource['_id'] = new ObjectId();
        $endpoint = $this->build($resource, $collection);

        try {
            $endpoint->transformQuery();
        } catch (\Throwable $e) {
            throw new Exception\InvalidFilter('filters must be tubee (MongoDB) compatible dql');
        }

        $endpoint->setup();

        if ($resource['data']['type'] === EndpointInterface::TYPE_SOURCE) {
            $this->ensureIndex($collection, $resource['data']['options']['import']);
        }

        $resource['namespace'] = $collection->getResourceNamespace()->getName();
        $resource['collection'] = $collection->getName();

        return $this->resource_factory->addTo($this->db->{self::COLLECTION_NAME}, $resource);
    }

    /**
     * Update.
     */
    public function update(EndpointInterface $resource, array $data): bool
    {
        $data['name'] = $resource->getName();
        $data['kind'] = $resource->getKind();

        $data = $this->secret_factory->resolve($resource->getCollection()->getResourceNamespace(), $data);
        $data = $this->resource_factory->validate($data);
        $data = Validator::validate($data);

        if ($data['data']['type'] === EndpointInterface::TYPE_SOURCE) {
            $this->ensureIndex($resource->getCollection(), $data['data']['options']['import']);
        }

        foreach ($data['secrets'] as $secret) {
            $data = Helper::deleteArrayValue($data, $secret['to']);
        }

        $data['_id'] = $resource->getId();

        $endpoint = $this->build($data, $resource->getCollection());

        try {
            $endpoint->transformQuery();
        } catch (\Throwable $e) {
            throw new Exception\InvalidFilter('filters must be tubee (MongoDb) compatible dql');
        }

        $endpoint->setup();

        return $this->resource_factory->updateIn($this->db->{self::COLLECTION_NAME}, $resource, $data);
    }

    /**
     * Change stream.
     */
    public function watch(CollectionInterface $collection, ?ObjectIdInterface $after = null, bool $existing = true, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        $filter = $this->prepareQuery($collection, $query);
        $that = $this;

        return $this->resource_factory->watchFrom($this->db->{self::COLLECTION_NAME}, $after, $existing, $filter, function (array $resource) use ($collection, $that) {
            return $that->build($resource, $collection);
        }, $offset, $limit, $sort);
    }

    /**
     * Build instance.
     */
    public function build(array $resource, CollectionInterface $collection)
    {
        $factory = EndpointInterface::ENDPOINT_MAP[$resource['kind']].'\\Factory';
        $resource = $this->secret_factory->resolve($collection->getResourceNamespace(), $resource);

        return $this->resource_factory->initResource($factory::build($resource, $collection, $this->workflow_factory, $this->logger));
    }

    /**
     * Prepare query.
     */
    protected function prepareQuery(CollectionInterface $collection, ?array $query = null): array
    {
        $filter = [
            'namespace' => $collection->getResourceNamespace()->getName(),
            'collection' => $collection->getName(),
        ];

        if (!empty($query)) {
            $filter = [
                '$and' => [$filter, $query],
            ];
        }

        return $filter;
    }

    /**
     * Ensure indexes.
     */
    protected function ensureIndex(CollectionInterface $collection, array $fields): string
    {
        $list = iterator_to_array($this->db->{$collection->getCollection()}->listIndexes());
        $keys = array_fill_keys($fields, 1);

        $this->logger->debug('verify if mongodb index exists for import attributes [{import}]', [
            'category' => get_class($this),
            'import' => $keys,
        ]);

        foreach ($list as $index) {
            if ($index['key'] === $keys) {
                $this->logger->debug('found existing mongodb index ['.$index['name'].']', [
                    'category' => get_class($this),
                    'fields' => $keys,
                ]);

                return $index['name'];
            }
        }

        $this->logger->info('create new mongodb index', [
            'category' => get_class($this),
        ]);

        return $this->db->{$collection->getCollection()}->createIndex($keys);
    }
}
