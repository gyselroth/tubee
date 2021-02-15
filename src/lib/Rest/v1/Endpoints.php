<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Rest\v1;

use Fig\Http\Message\StatusCodeInterface;
use Lcobucci\ContentNegotiation\UnformattedResponse;
use Micro\Auth\Identity;
use MongoDB\BSON\ObjectIdInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rs\Json\Patch;
use Tubee\Acl;
use Tubee\Endpoint\Factory as EndpointFactory;
use Tubee\Log\Factory as LogFactory;
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
     * Log factory.
     *
     * @var LogFactory
     */
    protected $log_factory;

    /**
     * Init.
     */
    public function __construct(ResourceNamespaceFactory $namespace_factory, EndpointFactory $endpoint_factory, Acl $acl, LogFactory $log_factory)
    {
        $this->namespace_factory = $namespace_factory;
        $this->endpoint_factory = $endpoint_factory;
        $this->acl = $acl;
        $this->log_factory = $log_factory;
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

        if (isset($query['watch'])) {
            $cursor = $this->endpoint_factory->watch($collection, null, isset($query['stream']), $query['query'], $query['offset'], $query['limit'], $query['sort']);

            return Helper::watchAll($request, $identity, $this->acl, $cursor);
        }

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

        return (new Response())->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);
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
        $doc = ['data' => $endpoint->getData(), 'secrets' => $endpoint->getSecrets()];

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

    /**
     * Entrypoint.
     */
    public function getAllLogs(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection, string $endpoint): ResponseInterface
    {
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
        ], $request->getQueryParams());

        $filter = [
            'namespace' => $namespace,
            'collection' => $collection,
            'endpoint' => $endpoint,
        ];

        if (!empty($query['query'])) {
            $filter = ['$and' => [$filter, $query['query']]];
        }

        if (isset($query['watch'])) {
            $logs = $this->log_factory->watch(null, isset($query['stream']), $filter, (int) $query['offset'], (int) $query['limit'], $query['sort']);

            return Helper::watchAll($request, $identity, $this->acl, $logs);
        }

        $logs = $this->log_factory->getAll($filter, (int) $query['offset'], (int) $query['limit'], $query['sort']);

        return Helper::getAll($request, $identity, $this->acl, $logs);
    }

    /**
     * Entrypoint.
     */
    public function getOneLog(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection, string $endpoint, ObjectIdInterface $log): ResponseInterface
    {
        $resource = $this->log_factory->getOne($log);

        return Helper::getOne($request, $identity, $resource);
    }
}
