<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\DataObjectRelation;

use Generator;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\ObjectIdInterface;
use Tubee\DataObject\DataObjectInterface;
use Tubee\DataObjectRelation;
use Tubee\Resource\Factory as ResourceFactory;
use Tubee\ResourceNamespace\ResourceNamespaceInterface;

class Factory extends ResourceFactory
{
    /**
     * Collection name.
     */
    public const COLLECTION_NAME = 'relations';

    /**
     * Has resource.
     */
    public function has(ResourceNamespaceInterface $namespace, string $name): bool
    {
        return $this->db->{self::COLLECTION_NAME}->count([
            'namespace' => $namespace->getName(),
            'name' => $name,
        ]) > 0;
    }

    /**
     * Get one.
     */
    public function getOne(ResourceNamespaceInterface $namespace, string $name): DataObjectRelationInterface
    {
        $resource = $this->db->{self::COLLECTION_NAME}->findOne([
            'namespace' => $namespace->getName(),
            'name' => $name,
        ], [
            'projection' => ['history' => 0],
        ]);

        if ($resource === null) {
            throw new Exception\NotFound('relation '.$name.' was not found');
        }

        return $this->build($resource);
    }

    /**
     * Get one from object.
     */
    public function getOneFromObject(DataObjectInterface $object, string $name): DataObjectRelationInterface
    {
        $relation = [
            'namespace' => $object->getCollection()->getResourceNamespace()->getName(),
            'collection' => $object->getCollection()->getName(),
            'object' => $object->getName(),
        ];

        $filter = [
            'name' => $name,
            'data.relation.namespace' => $relation['namespace'],
            'data.relation.collection' => $relation['collection'],
            'data.relation.object' => $relation['object'],
        ];

        $resource = $this->db->{self::COLLECTION_NAME}->findOne($filter);

        if ($resource === null) {
            throw new Exception\NotFound('relation '.$name.' was not found');
        }

        $object_1 = array_shift($resource['data']['relation']);
        $object_2 = array_shift($resource['data']['relation']);
        $related = $object_1;

        if ($object_1 == $relation) {
            $related = $object_2;
        }

        $related = $object->getCollection()->getResourceNamespace()->switch($related['namespace'])->getCollection($related['collection'])->getObject(['name' => $related['object']]);

        return $this->build($resource, $related);
    }

    /**
     * Get all from object.
     */
    public function getAllFromObject(DataObjectInterface $object, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        $relation = [
            'namespace' => $object->getCollection()->getResourceNamespace()->getName(),
            'collection' => $object->getCollection()->getName(),
            'object' => $object->getName(),
        ];

        $filter = [
            'data.relation.namespace' => $relation['namespace'],
            'data.relation.collection' => $relation['collection'],
            'data.relation.object' => $relation['object'],
        ];

        if (!empty($query)) {
            $filter = [
                '$and' => [$filter, $query],
            ];
        }

        return $this->getAllFrom($this->db->{self::COLLECTION_NAME}, $filter, $offset, $limit, $sort, function (array $resource) use ($object, $relation) {
            $object_1 = $resource['data']['relation'][0];
            $object_2 = $resource['data']['relation'][1];
            $related = $object_1;

            if ($object_1 == $relation) {
                $related = $object_2;
            }

            $related = $object->getCollection()->getResourceNamespace()->switch($related['namespace'])->getCollection($related['collection'])->getObject(['name' => $related['object']]);

            return $this->build($resource, $related);
        });
    }

    /**
     * {@inheritdoc}
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

        return $this->getAllFrom($this->db->{self::COLLECTION_NAME}, $filter, $offset, $limit, $sort, function (array $resource) {
            return $this->build($resource);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function deleteFromObject(DataObjectInterface $object_1, DataObjectInterface $object_2, bool $simulate = false): bool
    {
        $relations = [
            [
                'data.relation.namespace' => $object_1->getCollection()->getResourceNamespace()->getName(),
                'data.relation.collection' => $object_1->getCollection()->getName(),
                'data.relation.object' => $object_1->getName(),
            ], [
                'data.relation.namespace' => $object_2->getCollection()->getResourceNamespace()->getName(),
                'data.relation.collection' => $object_2->getCollection()->getName(),
                'data.relation.object' => $object_2->getName(),
            ],
        ];

        $this->db->{self::COLLECTION_NAME}->remove([
            '$and' => $relations,
        ]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function createOrUpdate(DataObjectInterface $object_1, DataObjectInterface $object_2, array $context = [], bool $simulate = false, ?array $endpoints = null): ObjectIdInterface
    {
        $relations = [
            [
                'data.relation.namespace' => $object_1->getCollection()->getResourceNamespace()->getName(),
                'data.relation.collection' => $object_1->getCollection()->getName(),
                'data.relation.object' => $object_1->getName(),
            ], [
                'data.relation.namespace' => $object_2->getCollection()->getResourceNamespace()->getName(),
                'data.relation.collection' => $object_2->getCollection()->getName(),
                'data.relation.object' => $object_2->getName(),
            ],
        ];

        $name = new ObjectId();
        $resource = [
            '_id' => $name,
            'name' => (string) $name,
            'data' => [
                'relation' => [
                    [
                        'namespace' => $relations[0]['data.relation.namespace'],
                        'collection' => $relations[0]['data.relation.collection'],
                        'object' => $relations[0]['data.relation.object'],
                    ],
                    [
                        'namespace' => $relations[1]['data.relation.namespace'],
                        'collection' => $relations[1]['data.relation.collection'],
                        'object' => $relations[1]['data.relation.object'],
                    ],
                ],
                'context' => $context,
            ],
            'namespace' => $object_1->getCollection()->getResourceNamespace()->getName(),
        ];

        $exists = $this->db->{self::COLLECTION_NAME}->findOne([
            '$and' => $relations,
        ]);

        if ($endpoints !== null) {
            $resource['endpoints'] = $endpoints;
        }

        if ($exists !== null) {
            $data = [
                'data' => $exists['data'],
                'endpoints' => array_replace_recursive($exists['endpoints'], $endpoints),
            ];

            $exists = $this->build($exists);
            $this->updateIn($this->db->{self::COLLECTION_NAME}, $exists, $data);

            return $exists->getId();
        }

        return $this->addTo($this->db->{self::COLLECTION_NAME}, $resource);
    }

    /**
     * Add.
     */
    public function add(ResourceNamespaceInterface $namespace, array $resource): ObjectIdInterface
    {
        $resource['kind'] = 'DataObjectRelation';
        $resource = $this->validate($resource);

        $resource['_id'] = new ObjectId();
        if (!isset($resource['name'])) {
            $resource['name'] = (string) $resource['_id'];
        }

        if ($this->has($namespace, $resource['name'])) {
            throw new Exception\NotUnique('relation '.$resource['name'].' does already exists');
        }

        $resource['namespace'] = $namespace->getName();

        return $this->addTo($this->db->{self::COLLECTION_NAME}, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function update(DataObjectRelationInterface $resource, array $data): bool
    {
        $data['name'] = $resource->getName();
        $data['kind'] = $resource->getKind();
        $data = $this->validate($data);

        return $this->updateIn($this->db->{self::COLLECTION_NAME}, $resource, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteOne(DataObjectRelationInterface $relation, bool $simulate = false): bool
    {
        $this->logger->info('delete object relation ['.$relation->getId()().'] from ['.self::COLLECTION_NAME.']', [
            'category' => get_class($this),
        ]);

        $this->db->{self::COLLECTION_NAME}->deleteOne(['_id' => $relation->getId()]);

        return true;
    }

    /**
     * Change stream.
     */
    public function watch(ResourceNamespaceInterface $namespace, ?ObjectIdInterface $after = null, bool $existing = true, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        return $this->watchFrom($this->db->{self::COLLECTION_NAME}, $after, $existing, $query, function (array $resource) {
            return $this->build($resource);
        }, $offset, $limit, $sort);
    }

    /**
     * Build.
     */
    public function build(array $resource, ?DataObjectInterface $object = null): DataObjectRelationInterface
    {
        return $this->initResource(new DataObjectRelation($resource, $object));
    }
}
