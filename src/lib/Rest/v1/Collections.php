<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Rest\v1;

use Fig\Http\Message\StatusCodeInterface;
use Lcobucci\ContentNegotiation\UnformattedResponse;
use Micro\Auth\Identity;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rs\Json\Patch;
use Tubee\Acl;
use Tubee\Collection\Factory as CollectionFactory;
use Tubee\ResourceNamespace\Factory as ResourceNamespaceFactory;
use Tubee\Rest\Helper;
use Zend\Diactoros\Response;

class Collections
{
    /**
     * namespace factory.
     *
     * @var ResourceNamespaceFactory
     */
    protected $namespace_factory;

    /**
     * collection factory.
     *
     * @var CollectionFactory
     */
    protected $collection_factory;

    /**
     * Acl.
     *
     * @var Acl
     */
    protected $acl;

    /**
     * Init.
     */
    public function __construct(ResourceNamespaceFactory $namespace_factory, CollectionFactory $collection_factory, Acl $acl)
    {
        $this->namespace_factory = $namespace_factory;
        $this->collection_factory = $collection_factory;
        $this->acl = $acl;
    }

    /**
     * Entrypoint.
     */
    public function getAll(ServerRequestInterface $request, Identity $identity, string $namespace): ResponseInterface
    {
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
        ], $request->getQueryParams());

        $namespace = $this->namespace_factory->getOne($namespace);
        $collections = $namespace->getCollections($query['query'], (int) $query['offset'], (int) $query['limit'], $query['sort']);

        return Helper::getAll($request, $identity, $this->acl, $collections);
    }

    /**
     * Entrypoint.
     */
    public function getOne(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection): ResponseInterface
    {
        $namespace = $this->namespace_factory->getOne($namespace);
        $collection = $namespace->getCollection($collection);

        return Helper::getOne($request, $identity, $collection);
    }

    /**
     * Delete.
     */
    public function delete(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection): ResponseInterface
    {
        $namespace = $this->namespace_factory->getOne($namespace);
        $this->collection_factory->deleteOne($namespace, $collection);

        return (new Response())->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);
    }

    /**
     * Create.
     */
    public function post(ServerRequestInterface $request, Identity $identity, string $namespace): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();

        $namespace = $this->namespace_factory->getOne($namespace);
        $id = $this->collection_factory->add($namespace, $body);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_CREATED),
            $this->collection_factory->getOne($namespace, $body['name'])->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Patch.
     */
    public function patch(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();
        $namespace = $this->namespace_factory->getOne($namespace);
        $collection = $namespace->getCollection($collection);
        $doc = ['data' => $collection->getData()];

        $patch = new Patch(json_encode($doc), json_encode($body));
        $patched = $patch->apply();
        $update = json_decode($patched, true);

        $this->collection_factory->update($collection, $update);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $this->collection_factory->getOne($namespace, $collection->getName())->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Watch.
     */
    public function watchAll(ServerRequestInterface $request, Identity $identity, string $namespace): ResponseInterface
    {
        $query = array_merge([
            'offset' => null,
            'limit' => null,
            'existing' => true,
        ], $request->getQueryParams());

        $namespace = $this->namespace_factory->getOne($namespace);
        $cursor = $this->collection_factory->watch($namespace, null, true, $query['query'], $query['offset'], $query['limit'], $query['sort']);

        return Helper::watchAll($request, $identity, $this->acl, $cursor);
    }
}
