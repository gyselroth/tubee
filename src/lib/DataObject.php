<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Psr\Http\Message\ServerRequestInterface;
use Tubee\DataObject\DataObjectInterface;
use Tubee\DataType\DataTypeInterface;
use Tubee\Resource\AttributeResolver;

class DataObject implements DataObjectInterface
{
    /**
     * Resource.
     *
     * @var array
     */
    protected $resource = [
        'version' => 1,
        'data' => [],
        'endpoints' => [],
        'deleted' => null,
        'changed' => null,
    ];

    /**
     * Datatype.
     *
     * @var DataTypeInterface
     */
    protected $datatype;

    /**
     * Data object.
     */
    public function __construct(array $resource, DataTypeInterface $datatype)
    {
        $this->resource = array_merge($this->resource, $resource);
        $this->datatype = $datatype;
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
            'metadata' => [
                'id' => (string) $this->getId(),
                'version' => $this->getVersion(),
                'created' => $object->getCreated()->toDateTime()->format('c')
            },
            'spec' => $this->getData(),
            'status' => function ($object) {
                $endpoints = $object->getEndpoints();
                foreach ($endpoints as &$endpoint) {
                    //$endpoint['last_sync'] = $endpoint['last_sync']->toDateTime()->format('c');
                }

                return $endpoints;
            },
        ];

        return AttributeResolver::resolve($request, $this, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function getHistory(?int $offset = null, ?int $limit = null): Iterable
    {
        return $this->datatype->getObjectHistory($this->resource['_id']);
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion(): int
    {
        return $this->resource['version'];
    }

    /**
     * {@inheritdoc}
     */
    public function getChanged(): ?UTCDateTime
    {
        return $this->resource['changed'];
    }

    /**
     * {@inheritdoc}
     */
    public function getCreated(): UTCDateTime
    {
        return $this->resource['created'];
    }

    /**
     * {@inheritdoc}
     */
    public function getDeleted(): ?UTCDateTime
    {
        return $this->resource['deleted'];
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
}
