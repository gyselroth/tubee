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
use Tubee\Collection\CollectionInterface;
use Tubee\Collection\Factory as CollectionFactory;
use Tubee\Resource\AbstractResource;
use Tubee\Resource\AttributeResolver;
use Tubee\ResourceNamespace\Factory as ResourceNamespaceFactory;
use Tubee\ResourceNamespace\ResourceNamespaceInterface;

class ResourceNamespace extends AbstractResource implements ResourceNamespaceInterface
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
     * @var CollectionFactory
     */
    protected $collection_factory;

    /**
     * ResourceNamespace factory.
     *
     * @var ResourceNamespaceFactory
     */
    protected $namespace_factory;

    /**
     * Initialize.
     */
    public function __construct(string $name, ResourceNamespaceFactory $namespace_factory, CollectionFactory $collection_factory, array $resource = [])
    {
        $this->name = $name;
        $this->resource = $resource;
        $this->namespace_factory = $namespace_factory;
        $this->collection_factory = $collection_factory;
    }

    /**
     * Decorate.
     */
    public function decorate(ServerRequestInterface $request): array
    {
        $resource = [
            'kind' => 'Namespace',
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
    public function getCollectionFactory(): CollectionFactory
    {
        return $this->collection_factory;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCollection(string $name): bool
    {
        return $this->collection_factory->has($this, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(string $name): CollectionInterface
    {
        return $this->collection_factory->getOne($this, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function getCollections(array $collections = [], ?int $offset = null, ?int $limit = null): Generator
    {
        return $this->collection_factory->getAll($this, $collections, $offset, $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function switch(string $name): ResourceNamespaceInterface
    {
        return $this->namespace_factory->getOne($name);
    }
}
