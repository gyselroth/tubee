<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use Generator;
use MongoDB\BSON\ObjectIdInterface;
use MongoDB\BSON\UTCDateTime;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Tubee\DataObject\DataObjectInterface;
use Tubee\DataObject\Factory as DataObjectFactory;
use Tubee\DataType\DataTypeInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Endpoint\Factory as EndpointFactory;
use Tubee\Mandator\MandatorInterface;
use Tubee\Resource\AbstractResource;
use Tubee\Resource\AttributeResolver;
use Tubee\Schema\SchemaInterface;

class DataType extends AbstractResource implements DataTypeInterface
{
    /**
     * DataType name.
     *
     * @var string
     */
    protected $name;

    /**
     * Mandator.
     *
     * @var MandatorInterface
     */
    protected $mandator;

    /**
     * Schema.
     *
     * @var SchemaInterface
     */
    protected $schema;

    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * MongoDB aggregation pipeline.
     *
     * @var iterable
     */
    protected $dataset = [];

    /**
     * Endpoints.
     *
     * @var array
     */
    protected $endpoints = [];

    /**
     * Dataobject factory.
     *
     * @var DataObjectFactory
     */
    protected $object_factory;

    /**
     * Endpoint factory.
     *
     * @var EndpointFactory
     */
    protected $endpoint_factory;

    /**
     * Collection name.
     *
     * @var string
     */
    protected $collection;

    /**
     * Initialize.
     */
    public function __construct(string $name, MandatorInterface $mandator, EndpointFactory $endpoint_factory, DataObjectFactory $object_factory, SchemaInterface $schema, LoggerInterface $logger, array $resource = [])
    {
        $this->resource = $resource;
        $this->name = $name;
        $this->collection = 'objects'.'.'.$mandator->getName().'.'.$name;
        $this->mandator = $mandator;
        $this->schema = $schema;
        $this->endpoint_factory = $endpoint_factory;
        $this->logger = $logger;
        $this->object_factory = $object_factory;
    }

    /**
     * Get collection.
     */
    public function getCollection(): string
    {
        return $this->collection;
    }

    /**
     * Get schema.
     */
    public function getSchema(): SchemaInterface
    {
        return $this->schema;
    }

    /**
     * {@inheritdoc}
     */
    public function getMandator(): MandatorInterface
    {
        return $this->mandator;
    }

    /**
     * Decorate.
     */
    public function decorate(ServerRequestInterface $request): array
    {
        $resource = [
            '_links' => [
                'self' => ['href' => (string) $request->getUri()],
                'mandator' => ['href' => ($mandator = (string) $request->getUri()->withPath('/api/v1/mandators/'.$this->getMandator()->getName()))],
            ],
            'kind' => 'DataType',
            'data' => [
                'schema' => $this->schema->getSchema(),
            ],
       ];

        return AttributeResolver::resolve($request, $this, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function hasEndpoint(string $name): bool
    {
        return $this->endpoint_factory->has($this, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function getEndpoint(string $name): EndpointInterface
    {
        return $this->endpoint_factory->getOne($this, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function getEndpoints(array $endpoints = [], ?int $offset = null, ?int $limit = null): Generator
    {
        return $this->endpoint_factory->getAll($this, $endpoints, $offset, $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceEndpoints(array $endpoints = [], ?int $offset = null, ?int $limit = null): Generator
    {
        $query = ['type' => 'source'];
        if ($endpoints !== []) {
            $query = ['$and' => [$query, $endpoints]];
        }

        return $this->endpoint_factory->getAll($this, $query, $offset, $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function getDestinationEndpoints(array $endpoints = [], ?int $offset = null, ?int $limit = null): Generator
    {
        $query = ['type' => 'destination'];
        if ($endpoints !== []) {
            $query = ['$and' => [$query, $endpoints]];
        }

        return $this->endpoint_factory->getAll($this, $query, $offset, $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): string
    {
        return $this->mandator->getIdentifier().'::'.$this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectHistory(ObjectIdInterface $id, ?array $filter = null, ?int $offset = null, ?int $limit = null): Generator
    {
        return $this->object_factory->getObjectHistory($this, $id, $filter, $offset, $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function getObject(array $filter, bool $include_dataset = true, int $version = 0): DataObjectInterface
    {
        return $this->object_factory->getOne($this, $filter, $include_dataset, $version);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataset(): array
    {
        return $this->dataset;
    }

    /**
     * {@inheritdoc}
     */
    public function getObjects(array $query = [], bool $include_dataset = true, ?int $offset = null, ?int $limit = null): Generator
    {
        return $this->object_factory->getAll($this, $query, $include_dataset, $offset, $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function createObject(array $object, bool $simulate = false, ?array $endpoints = null): ObjectIdInterface
    {
        return $this->object_factory->create($this, $object, $simulate, $endpoints);
    }

    /**
     * {@inheritdoc}
     */
    public function enable(ObjectIdInterface $id, bool $simulate = false): bool
    {
        $this->logger->info('enable object ['.$id.'] in ['.$this->collection.']', [
            'category' => get_class($this),
        ]);

        $query = ['$unset' => ['deleted' => true]];

        if ($simulate === false) {
            //$this->db->{$this->collection}->updateOne(['_id' => $id], $query);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function disable(ObjectIdInterface $id, bool $simulate = false): bool
    {
        $this->logger->info('disable object ['.$id.'] in ['.$this->collection.']', [
            'category' => get_class($this),
        ]);

        $query = ['$set' => ['deleted' => new UTCDateTime()]];

        if ($simulate === false) {
            //$this->db->{$this->collection}->updateOne(['_id' => $id], $query);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function changeObject(DataObjectInterface $object, array $data, bool $simulate = false, array $endpoints = []): int
    {
        return $this->object_factory->update($this, $object, $data, $simulate, $endpoints);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteObject(ObjectIdInterface $id, bool $simulate = false): bool
    {
        return $this->object_factory->deleteOne($this, $id, $simulate);
    }

    /**
     * {@inheritdoc}
     */
    public function flush(bool $simulate = false): bool
    {
        return $this->object_factory->deleteAll($simulate);
    }
}
