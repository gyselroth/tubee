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
use Psr\Http\Message\ServerRequestInterface;
use Tubee\DataType\DataTypeInterface;
use Tubee\DataType\Factory as DataTypeFactory;
use Tubee\Mandator\MandatorInterface;
use Tubee\Resource\AbstractResource;
use Tubee\Resource\AttributeResolver;

class Mandator extends AbstractResource implements MandatorInterface
{
    /**
     * Name.
     *
     * @var string
     */
    protected $name;

    /**
     * Datatype.
     *
     * @var DataTypeFactory
     */
    protected $datatype;

    /**
     * Initialize.
     */
    public function __construct(string $name, DataTypeFactory $datatype, array $resource = [])
    {
        $this->name = $name;
        $this->resource = $resource;
        $this->datatype = $datatype;
    }

    /**
     * Decorate.
     */
    public function decorate(ServerRequestInterface $request): array
    {
        $resource = [
            '_links' => [
                'self' => ['href' => (string) $request->getUri()],
            ],
            'kind' => 'Mandator',
            'name' => $this->name,
        ];

        return AttributeResolver::resolve($request, $this, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function getDataTypeFactory(): DataTypeFactory
    {
        return $this->datatype;
    }

    /**
     * {@inheritdoc}
     */
    public function hasDataType(string $name): bool
    {
        return $this->datatype->has($this, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataType(string $name): DataTypeInterface
    {
        return $this->datatype->getOne($this, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataTypes(array $datatypes = [], ?int $offset = null, ?int $limit = null): Generator
    {
        return $this->datatype->getAll($this, $datatypes, $offset, $limit);
    }
}
