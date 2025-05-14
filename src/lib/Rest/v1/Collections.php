<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
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
use Tubee\Collection\Factory as CollectionFactory;
use Tubee\Log\Factory as LogFactory;
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
     * Log factory.
     *
     * @var LogFactory
     */
    protected $log_factory;

    /**
     * Init.
     */
    public function __construct(ResourceNamespaceFactory $namespace_factory, CollectionFactory $collection_factory, Acl $acl, LogFactory $log_factory)
    {
        $this->namespace_factory = $namespace_factory;
        $this->collection_factory = $collection_factory;
        $this->acl = $acl;
        $this->log_factory = $log_factory;
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

        if (isset($query['watch'])) {
            $cursor = $this->collection_factory->watch($namespace, null, isset($query['stream']), $query['query'], (int) $query['offset'], (int) $query['limit'], $query['sort']);

            return Helper::watchAll($request, $identity, $this->acl, $cursor);
        }

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
     * Entrypoint.
     */
    public function getAllLogs(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection): ResponseInterface
    {
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
        ], $request->getQueryParams());

        $filter = [
            'namespace' => $namespace,
            'collection' => $collection,
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
    public function getOneLog(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection, ObjectIdInterface $log): ResponseInterface
    {
        $resource = $this->log_factory->getOne($log);

        return Helper::getOne($request, $identity, $resource);
    }
}
