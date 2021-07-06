<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2021 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\ResourceNamespace;

use Generator;
use MongoDB\BSON\ObjectIdInterface;
use MongoDB\Database;
use Tubee\Collection\Factory as CollectionFactory;
use Tubee\Resource\Factory as ResourceFactory;
use Tubee\ResourceNamespace;

class Factory
{
    /**
     * Collection name.
     */
    public const COLLECTION_NAME = 'namespaces';

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
     * Datatype.
     *
     * @var CollectionFactory
     */
    protected $collection_factory;

    /**
     * Initialize.
     */
    public function __construct(Database $db, CollectionFactory $collection_factory, ResourceFactory $resource_factory)
    {
        $this->db = $db;
        $this->resource_factory = $resource_factory;
        $this->collection_factory = $collection_factory;
    }

    /**
     * Has namespace.
     */
    public function has(string $name): bool
    {
        return $this->db->{self::COLLECTION_NAME}->count(['name' => $name]) > 0;
    }

    /**
     * Get all.
     */
    public function getAll(?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        $that = $this;

        return $this->resource_factory->getAllFrom($this->db->{self::COLLECTION_NAME}, $query, $offset, $limit, $sort, function (array $resource) use ($that) {
            return $that->build($resource);
        });
    }

    /**
     * Get namespace.
     */
    public function getOne(string $name): ResourceNamespaceInterface
    {
        $result = $this->db->{self::COLLECTION_NAME}->findOne([
            'name' => $name,
        ], [
            'projection' => ['history' => 0],
        ]);

        if ($result === null) {
            throw new Exception\NotFound('namespace '.$name.' is not registered');
        }

        return $this->build($result);
    }

    /**
     * Update.
     */
    public function update(ResourceNamespaceInterface $resource, array $data): bool
    {
        $data['name'] = $resource->getName();
        $data['kind'] = $resource->getKind();
        $data = $this->resource_factory->validate($data);

        return $this->resource_factory->updateIn($this->db->{self::COLLECTION_NAME}, $resource, $data);
    }

    /**
     * Delete by name.
     */
    public function deleteOne(string $name): bool
    {
        $resource = $this->getOne($name);

        return $this->resource_factory->deleteFrom($this->db->{self::COLLECTION_NAME}, $resource->getId());
    }

    /**
     * Add namespace.
     */
    public function add(array $resource): ObjectIdInterface
    {
        $resource['kind'] = 'Namespace';
        $resource = $this->resource_factory->validate($resource);

        if ($this->has($resource['name'])) {
            throw new Exception\NotUnique('namespace '.$resource['name'].' does already exists');
        }

        return $this->resource_factory->addTo($this->db->{self::COLLECTION_NAME}, $resource);
    }

    /**
     * Change stream.
     */
    public function watch(?ObjectIdInterface $after = null, bool $existing = true, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        $that = $this;

        return $this->resource_factory->watchFrom($this->db->{self::COLLECTION_NAME}, $after, $existing, $query, function (array $resource) use ($that) {
            return $that->build($resource);
        }, $offset, $limit, $sort);
    }

    /**
     * Build instance.
     */
    public function build(array $resource): ResourceNamespaceInterface
    {
        return $this->resource_factory->initResource(new ResourceNamespace($resource['name'], $this, $this->collection_factory, $resource));
    }
}
