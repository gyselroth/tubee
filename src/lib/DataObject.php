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
use Psr\Http\Message\ServerRequestInterface;
use Tubee\DataObject\DataObjectInterface;
use Tubee\DataObjectRelation\Factory as DataObjectRelationFactory;
use Tubee\DataType\DataTypeInterface;
use Tubee\Resource\AbstractResource;
use Tubee\Resource\AttributeResolver;

class DataObject extends AbstractResource implements DataObjectInterface
{
    /**
     * Datatype.
     *
     * @var DataTypeInterface
     */
    protected $datatype;

    /**
     * Data object relation factory.
     *
     * @var DataObjectRelationFactory
     */
    protected $relation_factory;

    /**
     * Data object.
     */
    public function __construct(array $resource, DataTypeInterface $datatype, DataObjectRelationFactory $relation_factory)
    {
        $this->resource = $resource;
        $this->datatype = $datatype;
        $this->relation_factory = $relation_factory;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(ServerRequestInterface $request): array
    {
        $datatype = $this->getDataType();
        $mandator = $datatype->getMandator();

        $resource = [
            '_links' => [
                 'self' => ['href' => (string) $request->getUri()],
                 'mandator' => ['href' => ($mandator = (string) $request->getUri()->withPath('/api/v1/mandators/'.$mandator->getName()))],
                 'datatype' => ['href' => $mandator.'/datatypes'.$datatype->getName()],
            ],
            'kind' => 'DataObject',
            'data' => $this->getData(),
            'status' => function ($object) {
                $endpoints = $object->getEndpoints();
                foreach ($endpoints as &$endpoint) {
                    $endpoint['last_sync'] = $endpoint['last_sync']->toDateTime()->format('c');
                }

                return $endpoints;
            },
        ];

        return AttributeResolver::resolve($request, $this, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function getHistory(?array $query = null, ?int $offset = null, ?int $limit = null): Iterable
    {
        return $this->datatype->getObjectHistory($this->getId(), $query, $offset, $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function getData(): array
    {
        return $this->resource['data'];
    }

    /**
     * {@inheritdoc}
     */
    public function getDataType(): DataTypeInterface
    {
        return $this->datatype;
    }

    /**
     * Get endpoints.
     */
    public function getEndpoints(): array
    {
        return $this->resource['endpoints'];
    }

    /**
     * Add relation.
     */
    public function createRelation(DataObjectInterface $object, array $context = []): ObjectIdInterface
    {
        return $this->relation_factory->create($this, $object, $context);
    }

    /**
     * Get relatives.
     */
    public function getRelatives(): Generator
    {
        return $this->relation_factory->getAll($this);
    }
}
