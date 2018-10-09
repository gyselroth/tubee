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
use MongoDB\BSON\ObjectId;
use Tubee\DataObject\DataObjectInterface;
use Tubee\DataObjectRelation;
use Tubee\DataType\DataTypeInterface;
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
    public function has(DataObjectInterface $object, ObjectId $id): bool
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
    public function getOne(DataObjectInterface $object, ObjectId $id): DataObjectRelationInterface
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

        return $this->build($resource);
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(DataObjectInterface $object, ?array $query = null, ?int $offset = null, ?int $limit = null): Generator
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

        $result = $this->db->{self::COLLECTION_NAME}->find($filter, [
            'offset' => $offset,
            'limit' => $limit,
        ]);

        foreach ($result as $resource) {
            yield $resource['_id'] => $this->build($resource, $object);
        }

        return $this->db->{self::COLLECTION_NAME}->count($filter);
    }

    /**
     * {@inheritdoc}
     */
    public function create(DataObjectInterface $object_1, DataObjectInterface $object_2, array $context = [], bool $simulate = false, ?array $endpoints = null): ObjectId
    {
        $resource = [
            'datatype_1' => $object_1->getDataType()->getName(),
            'mandator_1' => $object_1->getDataType()->getMandator()->getName(),
            'object_1' => $object_1->getId(),
            'datatype_2' => $object_2->getDataType()->getName(),
            'mandator_2' => $object_2->getDataType()->getMandator()->getName(),
            'object_2' => $object_2->getId(),
            'context' => $context,
        ];

        return $this->addTo($this->db->{self::COLLECTION_NAME}, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function change(DataTypeInterface $datatype, DataObjectInterface $object, Iterable $data, bool $simulate = false, array $endpoints = []): int
    {
    }

    /**
     * {@inheritdoc}
     */
    public function deleteOne(DataTypeInterface $datatype, ObjectId $id, bool $simulate = false): bool
    {
        $this->logger->info('delete object ['.$id.'] from ['.$datatype->getCollection().']', [
            'category' => get_class($this),
        ]);

        $this->db->{$datatype->getCollection()}->deleteOne(['_id' => $id]);

        return true;
    }

    /**
     * Build.
     */
    public function build(array $resource, DataObjectInterface $object): DataObjectRelationInterface
    {
        return $this->initResource(new DataObjectRelation($object, $resource));
    }
}
