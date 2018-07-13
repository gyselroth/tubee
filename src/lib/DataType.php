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
use InvalidArgumentException;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Database;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Tubee\DataType\DataObject;
use Tubee\DataType\DataObject\DataObjectInterface;
use Tubee\DataType\DataTypeInterface;
use Tubee\DataType\Exception;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Mandator\MandatorInterface;
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
    public function __construct(array $resource, MandatorInterface $mandator, SchemaInterface $schema, Database $db, LoggerInterface $logger, ?Iterable $config = null)
    {
        $this->resource = $resource;
        //$this->collection = $name;
        $this->mandator = $mandator;
        $this->schema = $schema;
        $this->db = $db;
        $this->logger = $logger;
        //$this->setOptions($config);
    }

    /**
     * {@inheritdoc}
     */
    public function getMandator(): MandatorInterface
    {
        return $this->mandator;
    }

    /**
     * Set options.
     */
    /*public function setOptions(?Iterable $config = null): DataTypeInterface
    {
        if ($config === null) {
            return $this;
        }

        foreach ($config as $option => $value) {
            switch ($option) {
                case 'collection':
                    $this->collection = $value;

                break;
                case 'dataset':
                    $this->dataset = json_decode($value);

                break;
                default:
                    throw new InvalidArgumentException('unknown option '.$option.' given');
            }
        }

        return $this;
    }*/

    /**
     * Decorate.
     */
    public function decorate(ServerRequestInterface $request): array
    {
        return [
            '_links' => [
                'self' => ['href' => (string) $request->getUri()],
            ],
            'kind' => 'DataType',
            'name' => $this->resource['name'],
            'mandator' => $this->mandator->decorate($request, ['_links', 'name']),
            'schema' => $this->schema->getSchema(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function hasEndpoint(string $name): bool
    {
        return isset($this->endpoints[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function injectEndpoint(EndpointInterface $endpoint, string $name): DataTypeInterface
    {
        $this->logger->debug('inject endpoint ['.$name.'] of type ['.get_class($endpoint).']', [
            'category' => get_class($this),
        ]);

        if ($this->hasEndpoint($name)) {
            throw new Exception\EndpointNotUnique('endpoint '.$name.' is already registered');
        }

        $this->endpoints[$name] = $endpoint;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getEndpoint(string $name): EndpointInterface
    {
        if (!isset($this->endpoints[$name])) {
            throw new Exception\EndpointNotFound('endpoint '.$name.' is not registered in '.$this->getIdentifier());
        }

        return $this->endpoints[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getEndpoints(Iterable $endpoints = []): array
    {
        if (count($endpoints) === 0) {
            return $this->endpoints;
        }
        $list = [];
        foreach ($endpoints as $name) {
            if (!isset($this->endpoints[$name])) {
                throw new Exception\EndpointNotFound('endpoint '.$name.' is not registered in '.$this->getIdentifier());
            }
            $list[$name] = $this->endpoints[$name];
        }

        return $list;
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceEndpoints(Iterable $endpoints = []): array
    {
        return array_filter($this->getEndpoints($endpoints), function ($ep) {
            return $ep->getType() === EndpointInterface::TYPE_SOURCE;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getDestinationEndpoints(Iterable $endpoints = []): array
    {
        return array_filter($this->getEndpoints($endpoints), function ($ep) {
            return $ep->getType() === EndpointInterface::TYPE_DESTINATION;
        });
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
        return $this->resource['name'];
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): ObjectId
    {
        return $this->resource['id'];
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
            throw new Exception\ObjectNotFound('data object '.json_encode($filter).' not found in collection '.$this->collection);
        }
        if (count($objects) > 1) {
            throw new Exception\ObjectMultipleFound('multiple data objects found with filter '.json_encode($filter).' in collection '.$this->collection);
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
    public function getAll(Iterable $filter = [], bool $include_dataset = true, int $version = 0, ?int $offset = null, ?int $limit = null): Generator
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
    public function export(UTCDateTime $timestamp, Iterable $filter = [], Iterable $endpoints = [], bool $simulate = false, bool $ignore = false): bool
    {
        $this->logger->info('start write onto destination endpoints fom data type ['.$this->getIdentifier().']', [
            'category' => get_class($this),
        ]);

        if (count($this->getDestinationEndpoints()) === 0) {
            $this->logger->info('no destination endpoint active for datatype ['.$this->getIdentifier().'], skip write', [
                'category' => get_class($this),
            ]);

            return true;
        }

        //setup endpoints first
        foreach ($this->getDestinationEndpoints($endpoints) as $ep) {
            if ($ep->flushRequired()) {
                $ep->flush($simulate);
            }

            $ep->setup($simulate);
        }

        foreach ($this->getAll($filter) as $id => $object) {
            $this->logger->debug('process write for object ['.(string) $id.'] from data type ['.$this->getIdentifier().']', [
                'category' => get_class($this),
            ]);

            foreach ($this->getDestinationEndpoints($endpoints) as $ep) {
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
                        'mandator' => $this->getMandator()->getName(),
                        'datatype' => $this->getName(),
                        'endpoint' => $ep->getName(),
                        'object' => $object->getId(),
                        'exception' => $e,
                    ]);

                    if ($ignore === false) {
                        return false;
                    }
                }
            }
        }

        foreach ($this->getDestinationEndpoints($endpoints) as $n => $ep) {
            $ep->shutdown($simulate);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function import(UTCDateTime $timestamp, Iterable $filter = [], Iterable $endpoints = [], bool $simulate = false, bool $ignore = false): bool
    {
        $this->logger->info('start import from source endpoints into data type ['.$this->getIdentifier().']', [
            'category' => get_class($this),
        ]);

        if (count($this->getSourceEndpoints()) === 0) {
            $this->logger->info('no source endpoint active for datatype ['.$this->getIdentifier().'], skip import', [
                'category' => get_class($this),
            ]);

            return true;
        }

        foreach ($this->getSourceEndpoints($endpoints) as  $ep) {
            $this->logger->info('start import from source endpoint ['.$ep->getIdentifier().']', [
                'category' => get_class($this),
            ]);

            $this->ensureIndex($ep->getImport());

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
