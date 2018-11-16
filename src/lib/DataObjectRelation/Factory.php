<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\DataObjectRelation;

use Generator;
use MongoDB\BSON\ObjectIdInterface;
use Tubee\DataObject\DataObjectInterface;
use Tubee\DataObjectRelation;
use Tubee\Resource\Factory as ResourceFactory;

class Factory extends ResourceFactory
{
    /**
     * Collection name.
     */
    public const COLLECTION_NAME = 'relations';

    /**
     * Has resource.
     */
    public function has(DataObjectInterface $object, ObjectIdInterface $id): bool
    {
        return $this->db->{self::COLLECTION_NAME}->count([
            '_id' => $id,
            '$or' => [
                [
                    'object_1' => $object->getId(),
                ],
                [
                    'object_2' => $object->getId(),
                ],
            ],
        ]) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getOne(DataObjectInterface $object, ObjectIdInterface $id): DataObjectRelationInterface
    {
        $resource = $this->{self::COLLECTION_NAME}->findOne([
            '_id' => $id,
            '$or' => [
                [
                    'object_1' => $object->getId(),
                ],
                [
                    'object_2' => $object->getId(),
                ],
            ],
        ]);

        return $this->build($resource, $resource, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(DataObjectInterface $object, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        $filter = [
            '$or' => [
                [
                    'mandator_1' => $object->getDataType()->getMandator()->getName(),
                    'datatype_1' => $object->getDataType()->getName(),
                    'object_1' => $object->getId(),
                ],
                [
                    'mandator_2' => $object->getDataType()->getMandator()->getName(),
                    'datatype_2' => $object->getDataType()->getName(),
                    'object_2' => $object->getId(),
                ],
            ],
        ];

        if (!empty($query)) {
            $filter = [
                '$and' => [$filter, $query],
            ];
        }

        return $this->getAllFrom($this->db->{self::COLLECTION_NAME}, $filter, $offset, $limit, $sort, function (array $resource) use ($object) {
            if ($resource['object_1'] == $object->getId()) {
                $related = $object->getDataType()->getMandator()->switch($resource['mandator_2'])->getDataType($resource['datatype_2'])->getObject(['_id' => $resource['object_2']]);
            } else {
                $related = $object->getDataType()->getMandator()->switch($resource['mandator_1'])->getDataType($resource['datatype_1'])->getObject(['_id' => $resource['object_1']]);
            }

            return $this->build($resource, $object, $related);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function createOrUpdate(DataObjectInterface $object_1, DataObjectInterface $object_2, array $context = [], bool $simulate = false, ?array $endpoints = null): ObjectIdInterface
    {
        $resource = [
            'datatype_1' => $object_1->getDataType()->getName(),
            'mandator_1' => $object_1->getDataType()->getMandator()->getName(),
            'object_1' => $object_1->getId(),
            'datatype_2' => $object_2->getDataType()->getName(),
            'mandator_2' => $object_2->getDataType()->getMandator()->getName(),
            'object_2' => $object_2->getId(),
            'data' => $context,
         ];

        $exists = $this->db->{self::COLLECTION_NAME}->findOne([
            '$or' => [
                [
                    'object_1' => $object_1->getId(),
                    'object_2' => $object_2->getId(),
                ],
                [
                    'object_1' => $object_2->getId(),
                    'object_2' => $object_1->getId(),
                ],
            ],
        ]);

        if ($endpoints !== null) {
            $resource['endpoints'] = $endpoints;
        }

        if ($exists !== null) {
            $exists = $this->build($exists, $object_1, $object_2);
            $this->update($exists, $resource, $simulate);
        }

        return $this->addTo($this->db->{self::COLLECTION_NAME}, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function update(DataObjectRelationInterface $relation, array $data, bool $simulate = false, array $endpoints = []): bool
    {
        return $this->updateIn($this->db->{self::COLLECTION_NAME}, $relation, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteOne(DataObjectRelationInterface $relation, bool $simulate = false): bool
    {
        $this->logger->info('delete object ['.$id.'] from ['.$datatype->getCollection().']', [
            'category' => get_class($this),
        ]);

        $this->db->{$datatype->getCollection()}->deleteOne(['_id' => $id]);

        return true;
    }

    /**
     * Change stream.
     */
    public function watch(DataObjectInterface $object, ?ObjectIdInterface $after = null, bool $existing = true, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        return $this->watchFrom($this->db->{self::COLLECTION_NAME}, $after, $existing, $query, function (array $resource) use ($object) {
            return $this->build($resource, $object, $object);
        }, $offset, $limit, $sort);
    }

    /**
     * Build.
     */
    public function build(array $resource, DataObjectInterface $object, DataObjectInterface $related): DataObjectRelationInterface
    {
        return $this->initResource(new DataObjectRelation($resource, $object, $related));
    }
}
