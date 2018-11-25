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
        $datatype = $this->datatype->getName();
        $mandator = $this->datatype->getMandator()->getName();

        $resource = [
            '_links' => [
                'mandator' => ['href' => (string) $request->getUri()->withPath('/api/v1/mandators/'.$mandator)],
                'datatype' => ['href' => (string) $request->getUri()->withPath('/api/v1/mandators/'.$mandator.'/datatypes/'.$datatype)],
            ],
            'kind' => 'DataObject',
            'mandator' => $mandator,
            'datatype' => $datatype,
            'data' => $this->getData(),
            'status' => function ($object) {
                $endpoints = $object->getEndpoints();
                foreach ($endpoints as &$endpoint) {
                    $endpoint['last_sync'] = $endpoint['last_sync']->toDateTime()->format('c');
                    $endpoint['garbage'] = isset($endpoint['garbage']) ? $endpoint['garbage'] : false;
                    $endpoint['result'] = isset($endpoint['auto']) ? $endpoint['auto'] : null;
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
        if (!isset($this->resource['endpoints'])) {
            return [];
        }

        return $this->resource['endpoints'];
    }

    /**
     * Add relation.
     */
    public function createOrUpdateRelation(DataObjectInterface $object, array $context = [], bool $simulate = false, ?array $endpoints = null): ObjectIdInterface
    {
        return $this->relation_factory->createOrUpdate($this, $object, $context, $simulate, $endpoints);
    }

    /**
     * Get relatives.
     */
    public function getRelatives(): Generator
    {
        return $this->relation_factory->getAll($this);
    }
}
