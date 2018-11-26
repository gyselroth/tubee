<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\DataObject;

use Generator;
use MongoDB\BSON\ObjectIdInterface;
use MongoDB\Database;
use Psr\Log\LoggerInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\DataObject;
use Tubee\DataObjectRelation\Factory as DataObjectRelationFactory;
use Tubee\Resource\Factory as ResourceFactory;

class Factory extends ResourceFactory
{
    /**
     * Data object relation factory.
     *
     * @var DataObjectRelationFactory
     */
    protected $relation_factory;

    /**
     * Initialize.
     */
    public function __construct(Database $db, DataObjectRelationFactory $relation_factory, LoggerInterface $logger)
    {
        $this->relation_factory = $relation_factory;
        parent::__construct($db, $logger);
    }

    /**
     * Has namespace.
     */
    public function has(CollectionInterface $namespace, string $name): bool
    {
        return $this->db->collections->count([
            'name' => $name,
            'namespace' => $namespace->getName(),
        ]) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectHistory(CollectionInterface $collection, ObjectIdInterface $id, ?array $filter = null, ?int $offset = null, ?int $limit = null): Generator
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

        $current = $this->getOne($collection, ['_id' => $id]);
        yield $current;

        foreach ($this->db->{$collection->getCollection()}->aggregate($pipeline) as $version) {
            yield $version['version'] => $this->build(array_merge($current->toArray(), $version['history']), $collection);
        }

        $count[] = ['$count' => 'count'];
        //return $this->db->{$collection->getCollection()}->aggregate($count)['count'];
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getOne(CollectionInterface $collection, array $filter, bool $include_dataset = true, int $version = 0): DataObjectInterface
    {
        //$pipeline = $this->preparePipeline($filter, $include_dataset, $version);

        $this->logger->debug('find one object with pipeline [{pipeline}] from ['.$collection->getCollection().']', [
            'category' => get_class($this),
            //'pipeline' => $pipeline,
        ]);

        $cursor = $this->db->{$collection->getCollection()}->aggregate([['$match' => $filter]], [
            'allowDiskUse' => true,
        ]);
        $objects = iterator_to_array($cursor);

        if (count($objects) === 0) {
            throw new Exception\NotFound('data object '.json_encode($filter).' not found in collection '.$collection->getCollection());
        }
        if (count($objects) > 1) {
            throw new Exception\MultipleFound('multiple data objects found with filter '.json_encode($filter).' in collection '.$collection->getCollection());
        }

        return $this->build(array_shift($objects), $collection);
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(CollectionInterface $collection, ?array $query = null, bool $include_dataset = true, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        return $this->getAllFrom($this->db->{$collection->getCollection()}, $query, $offset, $limit, $sort, function (array $resource) use ($collection) {
            return $this->build($resource, $collection);
        });

        /*$total = $collection->count($query);
        $pipeline[] = ['$match' => $filter];
        $found = 0;
        $offset = $this->calcOffset($total, $offset);

        if ($offset !== null) {
            array_unshift($pipeline, ['$skip' => $offset]);
        }

        if ($limit !== null) {
            $pipeline[] = ['$limit' => $limit];
        }

        $pipeline  = array_merge($pipeline, $this->preparePipeline());
        $this->logger->debug('aggregate pipeline [{pipeline}] on collection ['.$collection->getCollection().']', [
            'category' => get_class($this),
            'pipeline' => $pipeline
        ]);

        $cursor = $this->db->{$collection->getCollection()}->aggregate($pipeline, [
            'allowDiskUse' => true,
        ]);

        foreach ($cursor as $object) {
            ++$found;
            yield (string) $object['_id'] => $this->build($object, $collection);
        }

        if ($found === 0) {
            $this->logger->warning('found no data objects in collection ['.$collection->getCollection().'] with aggregation pipeline [{pipeline}]', [
                'category' => get_class($this),
                'pipeline' => $pipeline
            ]);
        } else {
            $this->logger->info('found ['.$found.'] data objects in collection ['.$collection->getCollection().'] with aggregation pipeline [{pipeline}]', [
                'category' => get_class($this),
                'pipeline' => $pipeline
            ]);
        }

        return $total;*/
    }

    /**
     * {@inheritdoc}
     */
    public function create(CollectionInterface $collection, array $object, bool $simulate = false, ?array $endpoints = null): ObjectIdInterface
    {
        $collection->getSchema()->validate($object);

        $object = [
            'data' => $object,
            'endpoints' => $endpoints,
        ];

        return $this->addTo($this->db->{$collection->getCollection()}, $object, $simulate);
    }

    /**
     * {@inheritdoc}
     */
    public function update(CollectionInterface $collection, DataObjectInterface $object, array $data, bool $simulate = false, ?array $endpoints = null): bool
    {
        $collection->getSchema()->validate($data);

        $data = ['data' => $data];

        if ($endpoints !== null) {
            $existing = $object->getEndpoints();
            $data['endpoints'] = array_replace_recursive($existing, $endpoints);
        }

        return $this->updateIn($this->db->{$collection->getCollection()}, $object, $data, $simulate);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteOne(CollectionInterface $collection, ObjectIdInterface $id, bool $simulate = false): bool
    {
        return $this->deleteFrom($this->db->{$collection->getCollection()}, $id, $simulate);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll(CollectionInterface $collection, ObjectIdInterface $id, bool $simulate = false): bool
    {
        $this->logger->info('delete object ['.$id.'] from ['.$collection->getCollection().']', [
            'category' => get_class($this),
        ]);
    }

    /**
     * Change stream.
     */
    public function watch(CollectionInterface $collection, ?ObjectIdInterface $after = null, bool $existing = true, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        return $this->watchFrom($this->db->{$collection->getCollection()}, $after, $existing, $query, function (array $resource) use ($collection) {
            return $this->build($collection, $resource);
        }, $offset, $limit, $sort);
    }

    /**
     * Build.
     */
    public function build(array $resource, CollectionInterface $collection): DataObjectInterface
    {
        return $this->initResource(new DataObject($resource, $collection, $this->relation_factory));
    }

    /**
     * Prepare pipeline.
     */
    /*protected function preparePipeline(): array
    {
        $pipeline = [];
        $pipeline[] = [
            '$lookup' => [
                'from': 'relations',
                'localField': '_id',
                'foreignField': 'object_1',
                'as': 'relations_1',
            ];
        ];
        $pipeline[] = [
            '$lookup' => [
                'from': 'relations',
                'localField': '_id',
                'foreignField': 'object_2',
                'as': 'relations_2',
            ];
        ]

        return $pipeline;
    }*/
}
