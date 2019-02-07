<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use Generator;
use MongoDB\BSON\ObjectIdInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\DataObject\DataObjectInterface;
use Tubee\DataObjectRelation\Factory as DataObjectRelationFactory;
use Tubee\Resource\AbstractResource;
use Tubee\Resource\AttributeResolver;

class DataObject extends AbstractResource implements DataObjectInterface
{
    /**
     * Datatype.
     *
     * @var CollectionInterface
     */
    protected $collection;

    /**
     * Data object relation factory.
     *
     * @var DataObjectRelationFactory
     */
    protected $relation_factory;

    /**
     * Data object.
     */
    public function __construct(array $resource, CollectionInterface $collection, DataObjectRelationFactory $relation_factory)
    {
        $this->resource = $resource;
        $this->collection = $collection;
        $this->relation_factory = $relation_factory;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(ServerRequestInterface $request): array
    {
        $collection = $this->collection->getName();
        $namespace = $this->collection->getResourceNamespace()->getName();

        $resource = [
            '_links' => [
                'namespace' => ['href' => (string) $request->getUri()->withPath('/api/v1/namespaces/'.$namespace)],
                'collection' => ['href' => (string) $request->getUri()->withPath('/api/v1/namespaces/'.$namespace.'/collections/'.$collection)],
            ],
            'kind' => 'DataObject',
            'namespace' => $namespace,
            'collection' => $collection,
            'data' => $this->getData(),
            'status' => function ($object) {
                $endpoints = $object->getEndpoints();
                foreach ($endpoints as &$endpoint) {
                    $endpoint['last_sync'] = $endpoint['last_sync']->toDateTime()->format('c');
                    $endpoint['garbage'] = isset($endpoint['garbage']) ? $endpoint['garbage'] : false;
                    $endpoint['result'] = isset($endpoint['result']) ? $endpoint['result'] : null;
                }

                return ['endpoints' => $endpoints];
            },
        ];

        return AttributeResolver::resolve($request, $this, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function getHistory(?array $query = null, ?int $offset = null, ?int $limit = null): Iterable
    {
        return $this->collection->getObjectHistory($this->getId(), $query, $offset, $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        $resource = $this->resource;
        $resource['namespace'] = $this->collection->getResourceNamespace()->getName();
        $resource['collection'] = $this->collection->getName();

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(): CollectionInterface
    {
        return $this->collection;
    }

    /**
     * Get endpoints.
     */
    public function getEndpoints(): array
    {
        if (!isset($this->resource['endpoints'])) {
            return [];
        }

        return $this->resource['endpoints'];
    }

    /**
     * Add or update relation.
     */
    public function createOrUpdateRelation(DataObjectInterface $object, array $context = [], bool $simulate = false, ?array $endpoints = null): ObjectIdInterface
    {
        return $this->relation_factory->createOrUpdate($this, $object, $context, $simulate, $endpoints);
    }

    /**
     * Delete relation.
     */
    public function deleteRelation(DataObjectInterface $object, bool $simulate = false): bool
    {
        return $this->relation_factory->deleteFromObject($this, $object, $simulate);
    }

    /**
     * Get relatives.
     */
    public function getRelations(): Generator
    {
        return $this->relation_factory->getAllFromObject($this);
    }

    /**
     * Get relative.
     */
    public function getRelation(string $name): Generator
    {
        return $this->relation_factory->getOneFromObject($this, $name);
    }
}
