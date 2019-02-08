<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Collection;

use Generator;
use MongoDB\BSON\ObjectIdInterface;
use MongoDB\Database;
use Psr\Log\LoggerInterface;
use Tubee\Collection;
use Tubee\DataObject\Factory as DataObjectFactory;
use Tubee\Endpoint\Factory as EndpointFactory;
use Tubee\Resource\Factory as ResourceFactory;
use Tubee\ResourceNamespace\ResourceNamespaceInterface;
use Tubee\Schema;

class Factory
{
    /**
     * Collection name.
     */
    public const COLLECTION_NAME = 'collections';

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
     * Object factory.
     *
     * @var DataObjectFactory
     */
    protected $object_factory;

    /**
     * Endpoint.
     *
     * @var EndpointFactory
     */
    protected $endpoint_factory;

    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Initialize.
     */
    public function __construct(Database $db, ResourceFactory $resource_factory, EndpointFactory $endpoint_factory, DataObjectFactory $object_factory, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->resource_factory = $resource_factory;
        $this->endpoint_factory = $endpoint_factory;
        $this->object_factory = $object_factory;
        $this->logger = $logger;
    }

    /**
     * Has namespace.
     */
    public function has(ResourceNamespaceInterface $namespace, string $name): bool
    {
        return $this->db->{self::COLLECTION_NAME}->count([
            'name' => $name,
            'namespace' => $namespace->getName(),
        ]) > 0;
    }

    /**
     * Get all.
     */
    public function getAll(ResourceNamespaceInterface $namespace, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        $filter = [
            'namespace' => $namespace->getName(),
        ];

        if (!empty($query)) {
            $filter = [
                '$and' => [$filter, $query],
            ];
        }

        $that = $this;

        return $this->resource_factory->getAllFrom($this->db->{self::COLLECTION_NAME}, $filter, $offset, $limit, $sort, function (array $resource) use ($namespace, $that) {
            return $that->build($resource, $namespace);
        });
    }

    /**
     * Get one.
     */
    public function getOne(ResourceNamespaceInterface $namespace, string $name): CollectionInterface
    {
        $result = $this->db->{self::COLLECTION_NAME}->findOne([
            'name' => $name,
            'namespace' => $namespace->getName(),
        ], [
            'projection' => ['history' => 0],
        ]);

        if ($result === null) {
            throw new Exception\NotFound('collection '.$name.' is not registered');
        }

        return $this->build($result, $namespace);
    }

    /**
     * Delete by name.
     */
    public function deleteOne(ResourceNamespaceInterface $namespace, string $name): bool
    {
        $resource = $this->getOne($namespace, $name);

        return $this->resource_factory->deleteFrom($this->db->{self::COLLECTION_NAME}, $resource->getId());
    }

    /**
     * Add namespace.
     */
    public function add(ResourceNamespaceInterface $namespace, array $resource): ObjectIdInterface
    {
        $resource['kind'] = 'Collection';
        $resource = $this->resource_factory->validate($resource);

        if ($this->has($namespace, $resource['name'])) {
            throw new Exception\NotUnique('collection '.$resource['name'].' does already exists');
        }

        $resource['namespace'] = $namespace->getName();

        return $this->resource_factory->addTo($this->db->{self::COLLECTION_NAME}, $resource);
    }

    /**
     * Update.
     */
    public function update(CollectionInterface $resource, array $data): bool
    {
        $data['name'] = $resource->getName();
        $data['kind'] = 'Collection';
        $data = $this->resource_factory->validate($data);

        return $this->resource_factory->updateIn($this->db->{self::COLLECTION_NAME}, $resource, $data);
    }

    /**
     * Change stream.
     */
    public function watch(ResourceNamespaceInterface $namespace, ?ObjectIdInterface $after = null, bool $existing = true, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        $that = $this;

        return $this->resource_factory->watchFrom($this->db->{self::COLLECTION_NAME}, $after, $existing, $query, function (array $resource) use ($namespace, $that) {
            return $that->build($resource, $namespace);
        }, $offset, $limit, $sort);
    }

    /**
     * Build instance.
     */
    public function build(array $resource, ResourceNamespaceInterface $namespace): CollectionInterface
    {
        $schema = new Schema($resource['data']['schema'], $this->logger);

        return $this->resource_factory->initResource(new Collection($resource['name'], $namespace, $this->endpoint_factory, $this->object_factory, $schema, $this->logger, $resource));
    }
}
