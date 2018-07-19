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
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Database;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Tubee\DataObject\DataObjectInterface;
use Tubee\DataObject\Exception;
use Tubee\DataType\DataTypeInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Endpoint\Factory as EndpointFactory;
use Tubee\Mandator\MandatorInterface;
use Tubee\Resource\AttributeResolver;
use Tubee\Schema\SchemaInterface;

class DataType implements DataTypeInterface
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
     * Database.
     *
     * @var Database
     */
    protected $db;

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
     * Resource.
     *
     * @var array
     */
    protected $resource;

    /**
     * Initialize.
     */
    public function __construct(string $name, MandatorInterface $mandator, EndpointFactory $endpoint, SchemaInterface $schema, Database $db, LoggerInterface $logger, array $resource = [])
    {
        $this->resource = $resource;
        $this->name = $name;
        $this->collection = 'objects'.'.'.$mandator->getName().'.'.$namec;
        $this->mandator = $mandator;
        $this->schema = $schema;
        $this->endpoint = $endpoint;
        $this->db = $db;
        $this->logger = $logger;
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
            'metadata' => [
                'name' => $this->resource['name'],
                'id' => (string) $this->resource['_id'],
                'class' => get_class($this),
            ],
            'spec' => [
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
        return $this->endpoint->has($this, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function getEndpoint(string $name): EndpointInterface
    {
        return $this->endpoint->getOne($this, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function getEndpoints(array $endpoints = [], ?int $offset = null, ?int $limit = null): Generator
    {
        return $this->endpoint->getAll($this, $endpoints, $offset, $limit);
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

        return $this->endpoint->getAll($this, $query, $offset, $limit);
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

        return $this->endpoint->getAll($this, $query, $offset, $limit);
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
    public function getId(): ObjectId
    {
        return $this->resource['_id'];
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return $this->resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectHistory(ObjectId $id, ?array $filter = null, ?int $offset = null, ?int $limit = null): Iterable
    {
        $pipeline = [
            ['$match' => ['_id' => $id]],
            ['$unwind' => '$history'],
        ];

        $count = $pipeline;

        if ($filter !== null) {
            $pipeline[] = ['$match' => $filter];
        }

        if ($offset !== null) {
            $pipeline[] = ['$skip' => $offset];
        }

        if ($limit !== null) {
            $pipeline[] = ['$limit' => $limit];
        }

        foreach ($this->db->{$this->collection}->aggregate($pipeline) as $version) {
            yield $version['version'] => new DataObject($version, $this);
        }

        $count[] = ['$count' => 'count'];
        //return $this->db->{$this->collection}->aggregate($count)['count'];
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getOne(Iterable $filter, bool $include_dataset = true, int $version = 0): DataObjectInterface
    {
        $pipeline = $this->preparePipeline($filter, $include_dataset, $version);

        $this->logger->debug('find one object with pipeline [{pipeline}] from ['.$this->collection.']', [
            'category' => get_class($this),
            'pipeline' => $pipeline,
        ]);

        $cursor = $this->db->{$this->collection}->aggregate($pipeline, [
            'allowDiskUse' => true,
        ]);
        $objects = iterator_to_array($cursor);

        if (count($objects) === 0) {
            throw new Exception\NotFound('data object '.json_encode($filter).' not found in collection '.$this->collection);
        }
        if (count($objects) > 1) {
            throw new Exception\MultipleFound('multiple data objects found with filter '.json_encode($filter).' in collection '.$this->collection);
        }

        return new DataObject(array_shift($objects), $this);
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
    public function getAll(Iterable $filter = [], bool $include_dataset = true, ?int $offset = null, ?int $limit = null): Generator
    {
        $pipeline = [];
        if ($include_dataset === true) {
            $pipeline = $this->dataset;
            if (count($filter) > 0) {
                array_unshift($pipeline, ['$match' => $filter]);
            }
        } elseif (count($filter) > 0) {
            $pipeline = [['$match' => $filter]];
        }

        $found = 0;

        if ($offset !== null) {
            array_unshift($pipeline, ['$skip' => $offset]);
        }

        if ($limit !== null) {
            $pipeline[] = ['$limit' => $limit];
        }

        if (count($pipeline) === 0) {
            $this->logger->debug('empty pipeline given (no dataset configuration), collect all objects from ['.$this->collection.'] instead', [
                'category' => get_class($this),
            ]);
            $cursor = $this->db->{$this->collection}->find();
        } else {
            $this->logger->debug('aggregate pipeline ['.json_encode($pipeline).'] on collection ['.$this->collection.']', [
                'category' => get_class($this),
            ]);
            $cursor = $this->db->{$this->collection}->aggregate($pipeline, [
                'allowDiskUse' => true,
            ]);
        }

        foreach ($cursor as $object) {
            ++$found;
            yield (string) $object['_id'] => new DataObject($object, $this);
        }

        if ($found === 0) {
            $this->logger->warning('found no data objects in collection ['.$this->collection.'] with aggregation pipeline ['.json_encode($pipeline).']', [
                'category' => get_class($this),
            ]);
        } else {
            $this->logger->info('found ['.$found.'] data objects in collection ['.$this->collection.'] with aggregation pipeline ['.json_encode($pipeline).']', [
                'category' => get_class($this),
            ]);
        }

        return $this->db->{$this->collection}->count();
    }

    /**
     * {@inheritdoc}
     */
    public function create(Iterable $object, bool $simulate = false, ?array $endpoints = null): ObjectId
    {
        $this->schema->validate($object);

        $object = [
            'version' => 1,
            'created' => new UTCDateTime(),
            'data' => $object,
        ];

        $this->logger->info('create new object [{object}] in ['.$this->collection.']', [
            'category' => get_class($this),
            'object' => $object,
        ]);

        if ($simulate === false) {
            $result = $this->db->{$this->collection}->insertOne($object);

            return $result->getInsertedId();
        }

        return new ObjectId();
    }

    /**
     * {@inheritdoc}
     */
    public function enable(ObjectId $id, bool $simulate = false): bool
    {
        $this->logger->info('enable object ['.$id.'] in ['.$this->collection.']', [
            'category' => get_class($this),
        ]);

        $query = ['$unset' => ['deleted' => true]];

        if ($simulate === false) {
            $this->db->{$this->collection}->updateOne(['_id' => $id], $query);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function disable(ObjectId $id, bool $simulate = false): bool
    {
        $this->logger->info('disable object ['.$id.'] in ['.$this->collection.']', [
            'category' => get_class($this),
        ]);

        $query = ['$set' => ['deleted' => new UTCDateTime()]];

        if ($simulate === false) {
            $this->db->{$this->collection}->updateOne(['_id' => $id], $query);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function change(DataObjectInterface $object, Iterable $data, bool $simulate = false, array $endpoints = []): int
    {
        $this->schema->validate($data);

        $query = [
            '$set' => ['endpoints' => $endpoints],
        ];

        $version = $object->getVersion();

        if ($object->getData() !== $data) {
            ++$version;

            $query = array_merge($query, [
                '$set' => [
                    'data' => $data,
                    'changed' => new UTCDateTime(),
                    'version' => $version,
                ],
                '$addToSet' => ['history' => $object->toArray()],
            ]);

            $this->logger->info('change object ['.$object->getId().'] to version ['.$version.'] in ['.$this->collection.'] to [{data}]', [
                'category' => get_class($this),
                'data' => $data,
            ]);
        } else {
            $this->logger->info('object ['.$object->getId().'] version ['.$version.'] in ['.$this->collection.'] is already up2date', [
                'category' => get_class($this),
            ]);
        }

        if ($simulate === false) {
            $this->db->{$this->collection}->updateOne(['_id' => $object->getId()], $query);
        }

        return $version;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(ObjectId $id, bool $simulate = false): bool
    {
        $this->logger->info('delete object ['.$id.'] from ['.$this->collection.']', [
            'category' => get_class($this),
        ]);

        if ($simulate === false) {
            $this->db->{$this->collection}->deleteOne(['_id' => $id]);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(bool $simulate = false): bool
    {
        $this->logger->info('flush collection ['.$this->collection.']', [
            'category' => get_class($this),
        ]);

        if ($simulate === false) {
            $this->db->{$this->collection}->deleteMany([]);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function export(UTCDateTime $timestamp, array $filter = [], array $endpoints = [], bool $simulate = false, bool $ignore = false): bool
    {
        $this->logger->info('start export to destination endpoints from data type ['.$this->getIdentifier().']', [
            'category' => get_class($this),
        ]);

        $endpoints = iterator_to_array($this->getDestinationEndpoints($endpoints));

        foreach ($endpoints as $ep) {
            if ($ep->flushRequired()) {
                $ep->flush($simulate);
            }

            $ep->setup($simulate);
        }

        foreach ($this->getAll($filter) as $id => $object) {
            $this->logger->debug('process write for object ['.(string) $id.'] from data type ['.$this->getIdentifier().']', [
                'category' => get_class($this),
            ]);

            foreach ($endpoints as $ep) {
                $this->logger->info('start write onto destination endpoint ['.$ep->getIdentifier().']', [
                    'category' => get_class($this),
                ]);

                try {
                    foreach ($ep->getWorkflows() as $workflow) {
                        $this->logger->debug('start workflow ['.$workflow->getIdentifier().'] for the current object', [
                            'category' => get_class($this),
                        ]);

                        if ($workflow->export($object, $timestamp, $simulate) === true) {
                            $this->logger->debug('workflow ['.$workflow->getIdentifier().'] executed for the current object, skip any further workflows for the current data object', [
                                'category' => get_class($this),
                            ]);

                            continue 2;
                        }
                    }

                    $this->logger->debug('no workflow were executed within endpoint ['.$ep->getIdentifier().'] for the current object', [
                        'category' => get_class($this),
                    ]);
                } catch (\Exception $e) {
                    $this->logger->error('failed write object to destination endpoint ['.$ep->getIdentifier().']', [
                        'category' => get_class($this),
                        'object' => $object->getId(),
                        'exception' => $e,
                    ]);

                    if ($ignore === false) {
                        return false;
                    }
                }
            }
        }

        if (count($endpoints) === 0) {
            $this->logger->warning('no destination endpoint active for datatype ['.$this->getIdentifier().'], skip export', [
                'category' => get_class($this),
            ]);

            return true;
        }

        foreach ($endpoints as $n => $ep) {
            $ep->shutdown($simulate);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function import(UTCDateTime $timestamp, array $filter = [], array $endpoints = [], bool $simulate = false, bool $ignore = false): bool
    {
        $this->logger->info('start import from source endpoints into data type ['.$this->getIdentifier().']', [
            'category' => get_class($this),
        ]);

        $endpoints = $this->getSourceEndpoints($endpoints);

        foreach ($endpoints as $ep) {
            $this->logger->info('start import from source endpoint ['.$ep->getIdentifier().']', [
                'category' => get_class($this),
            ]);

            //$this->ensureIndex($ep->getImport());

            if ($ep->flushRequired()) {
                $this->flush($simulate);
            }

            $ep->setup($simulate);

            foreach ($ep->getAll($filter) as $id => $object) {
                $this->logger->debug('process import for object ['.$id.'] into data type ['.$this->getIdentifier().']', [
                    'category' => get_class($this),
                    'attributes' => $object,
                ]);

                try {
                    if (!is_iterable($object)) {
                        throw new Exception\InvalidObject('read() generator needs to yield iterable data');
                    }

                    foreach ($ep->getWorkflows() as $workflow) {
                        $this->logger->debug('start workflow ['.$workflow->getIdentifier().'] for the current object', [
                            'category' => get_class($this),
                        ]);

                        if ($workflow->import($this, $object, $timestamp, $simulate) === true) {
                            $this->logger->debug('workflow ['.$workflow->getIdentifier().'] executed for the current object, skip any further workflows for the current data object', [
                                'category' => get_class($this),
                            ]);

                            continue 2;
                        }
                    }

                    $this->logger->debug('no workflow were executed within endpoint ['.$ep->getIdentifier().'] for the current object', [
                        'category' => get_class($this),
                    ]);
                } catch (\Exception $e) {
                    $this->logger->error('failed import data object from source endpoint ['.$ep->getIdentifier().']', [
                        'category' => get_class($this),
                        'mandator' => $this->getMandator()->getName(),
                        'datatype' => $this->getName(),
                        'endpoint' => $ep->getName(),
                        'exception' => $e,
                    ]);

                    if ($ignore === false) {
                        return false;
                    }
                }
            }

            $this->garbageCollector($timestamp, $ep, $simulate, $ignore);
            $ep->shutdown($simulate);
        }

        if ($endpoints->getReturn() === 0) {
            $this->logger->warning('no source endpoint active for datatype ['.$this->getIdentifier().'], skip import', [
                'category' => get_class($this),
            ]);

            return true;
        }

        return true;
    }

    /**
     * Ensure indexes.
     */
    public function ensureIndex(array $fields): string
    {
        $list = iterator_to_array($this->db->{$this->collection}->listIndexes());
        $keys = array_fill_keys($fields, 1);

        $this->logger->debug('verify if mongodb index exists for import attributes [{import}]', [
            'category' => get_class($this),
            'import' => $keys,
        ]);

        foreach ($list as $index) {
            if ($index['key'] === $keys) {
                $this->logger->debug('found existing mongodb index ['.$index['name'].'] for import attributes', [
                    'category' => get_class($this),
                    'import' => $keys,
                ]);

                return $index['name'];
            }
        }

        $this->logger->info('create new mongodb index for import attributes', [
            'category' => get_class($this),
        ]);

        return $this->db->{$this->collection}->createIndex($keys);
    }

    /**
     * Prepare pipeline.
     */
    protected function preparePipeline(Iterable $filter, bool $include_dataset = true, int $version = 0): array
    {
        $pipeline = [];

        if ($include_dataset === true) {
            $pipeline = $this->dataset;
            array_unshift($pipeline, $filter);
        } else {
            $pipeline = [['$match' => $filter]];
        }

        if ($version === 0) {
            $pipeline[] = [
                '$project' => ['history' => false],
            ];
        } else {
            $pipeline[] = [
                '$unwind' => ['path' => '$history'],
            ];

            $pipeline[] = [
                '$match' => ['history.version' => $version],
            ];

            $pipeline[] = [
                '$replaceRoot' => ['newRoot' => '$history'],
            ];
        }

        return $pipeline;
    }

    /**
     * Garbage.
     */
    protected function garbageCollector(UTCDateTime $timestamp, EndpointInterface $endpoint, bool $simulate = false, bool $ignore = false): bool
    {
        $this->logger->info('start garbage collector workflows from data type ['.$this->getIdentifier().']', [
            'category' => get_class($this),
        ]);

        $filter = [
                '$or' => [
                    [
                        'endpoints.'.$endpoint->getName().'.last_sync' => [
                            '$lte' => $timestamp,
                        ],
                    ],
                    [
                        'endpoints' => [
                            '$exists' => 0,
                        ],
                    ],
                ],
        ];

        foreach ($this->getAll($filter, false) as $id => $object) {
            $this->logger->debug('process garbage workflows for garbage object ['.$id.'] from data type ['.$this->getIdentifier().']', [
                'category' => get_class($this),
            ]);

            try {
                foreach ($endpoint->getWorkflows() as $workflow) {
                    $this->logger->debug('start workflow ['.$workflow->getIdentifier().'] for the current garbage object', [
                        'category' => get_class($this),
                    ]);

                    if ($workflow->cleanup($object, $timestamp, $simulate) === true) {
                        $this->logger->debug('workflow ['.$workflow->getIdentifier().'] executed for the current garbage object, skip any further workflows for the current garbage object', [
                            'category' => get_class($this),
                        ]);

                        break;
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error('failed execute garbage collector for object ['.$id.'] from datatype ['.$this->getIdentifier().']', [
                    'category' => get_class($this),
                    'exception' => $e,
               ]);

                if ($ignore === false) {
                    return false;
                }
            }
        }

        return true;
    }
}
