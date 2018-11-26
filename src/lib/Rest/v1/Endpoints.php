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
use Tubee\Endpoint\Factory as EndpointFactory;
use Tubee\ResourceNamespace\Factory as ResourceNamespaceFactory;
use Tubee\Rest\Helper;
use Zend\Diactoros\Response;

class Endpoints
{
    /**
     * namespace factory.
     *
     * @var ResourceNamespaceFactory
     */
    protected $namespace_factory;

    /**
     * Endpoint factory.
     *
     * @var EndpointFactory
     */
    protected $endpoint_factory;

    /**
     * Acl.
     *
     * @var Acl
     */
    protected $acl;

    /**
     * Init.
     */
    public function __construct(ResourceNamespaceFactory $namespace_factory, EndpointFactory $endpoint_factory, Acl $acl)
    {
        $this->namespace_factory = $namespace_factory;
        $this->endpoint_factory = $endpoint_factory;
        $this->acl = $acl;
    }

    /**
     * Entrypoint.
     */
    public function getAll(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection): ResponseInterface
    {
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
        ], $request->getQueryParams());

        $collection = $this->namespace_factory->getOne($namespace)->getCollection($collection);
        $endpoints = $collection->getEndpoints($query['query'], (int) $query['offset'], (int) $query['limit'], $query['sort']);

        return Helper::getAll($request, $identity, $this->acl, $endpoints);
    }

    /**
     * Entrypoint.
     */
    public function getOne(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection, string $endpoint): ResponseInterface
    {
        $collection = $this->namespace_factory->getOne($namespace)->getCollection($collection);
        $endpoint = $collection->getEndpoint($endpoint);

        return Helper::getOne($request, $identity, $endpoint);
    }

    /**
     * Create.
     */
    public function post(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();

        $collection = $this->namespace_factory->getOne($namespace)->getCollection($collection);
        $id = $this->endpoint_factory->add($collection, $body);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_CREATED),
            $this->endpoint_factory->getOne($collection, $body['name'])->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Delete.
     */
    public function delete(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection, string $endpoint): ResponseInterface
    {
        $collection = $this->namespace_factory->getOne($namespace)->getCollection($collection);
        $this->endpoint_factory->deleteOne($collection, $endpoint);

        return(new Response())->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);
    }

    /**
     * Patch.
     */
    public function patch(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection, string $endpoint): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();
        $namespace = $this->namespace_factory->getOne($namespace);
        $collection = $namespace->getCollection($collection);
        $endpoint = $collection->getEndpoint($endpoint);
        $doc = ['data' => $endpoint->getData()];

        $patch = new Patch(json_encode($doc), json_encode($body));
        $patched = $patch->apply();
        $update = json_decode($patched, true);

        $this->endpoint_factory->update($endpoint, $update);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $collection->getEndpoint($endpoint->getName())->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Watch.
     */
    public function watchAll(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection): ResponseInterface
    {
        $query = array_merge([
            'offset' => null,
            'limit' => null,
            'existing' => true,
        ], $request->getQueryParams());

        $collection = $this->namespace_factory->getOne($namespace)->getCollection($collection);
        $cursor = $this->endpoint_factory->watch($collection, null, true, $query['query'], $query['offset'], $query['limit'], $query['sort']);

        return Helper::watchAll($request, $identity, $this->acl, $cursor);
    }

    /**
     * Entrypoint.
     */
    public function getAllObjects(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection, string $endpoint): ResponseInterface
    {
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
        ], $request->getQueryParams());

        $endpoint = $this->namespace_factory->getOne($namespace)->getCollection($collection)->getEndpoint($endpoint);
        $endpoint->setup();
        $objects = $endpoint->getAll($query['query'], (int) $query['offset'], (int) $query['limit'], $query['sort']);

        return Helper::getAll($request, $identity, $this->acl, $objects);
    }
}
