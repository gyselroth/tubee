<?php

declare(strict_types=1);

/**
 * tubee.io
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
use Tubee\Collection\Factory as CollectionFactory;
use Tubee\DataObject\Factory as DataObjectFactory;
use Tubee\Log\Factory as LogFactory;
use Tubee\ResourceNamespace\Factory as ResourceNamespaceFactory;
use Tubee\Rest\Helper;
use Tubee\Rest\Pager;
use Zend\Diactoros\Response;

class Objects
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
     * Object factory.
     *
     * @var DataObjectFactory
     */
    protected $object_factory;

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
    public function __construct(ResourceNamespaceFactory $namespace_factory, CollectionFactory $collection_factory, DataObjectFactory $object_factory, Acl $acl, LogFactory $log_factory)
    {
        $this->namespace_factory = $namespace_factory;
        $this->collection_factory = $collection_factory;
        $this->acl = $acl;
        $this->object_factory = $object_factory;
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
            $cursor = $this->object_factory->watch($collection, null, true, $query['query'], false, (int) $query['offset'], (int) $query['limit'], $query['sort']);

            return Helper::watchAll($request, $identity, $this->acl, $cursor);
        }

        $objects = $collection->getObjects($query['query'], false, (int) $query['offset'], (int) $query['limit'], $query['sort']);

        return Helper::getAll($request, $identity, $this->acl, $objects);
    }

    /**
     * Entrypoint.
     */
    public function getOne(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection, string $object): ResponseInterface
    {
        $collection = $this->namespace_factory->getOne($namespace)->getCollection($collection);
        $object = $collection->getObject(['name' => $object], false);

        return Helper::getOne($request, $identity, $object);
    }

    /**
     * Create object.
     */
    public function post(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection): ResponseInterface
    {
        $query = $request->getQueryParams();

        $body = array_merge([
            'data' => [],
            'endpoints' => null,
        ], $request->getParsedBody());

        $collection = $this->namespace_factory->getOne($namespace)->getCollection($collection);
        $id = $collection->createObject($body, false, $body['endpoints']);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_CREATED),
            $collection->getObject(['_id' => $id], false)->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Entrypoint.
     */
    public function getHistory(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection, string $object): ResponseInterface
    {
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
            'query' => [],
        ], $request->getQueryParams());

        $collection = $this->namespace_factory->getOne($namespace)->getCollection($collection);
        $object = $collection->getObject(['name' => $object], false);
        $history = $object->getHistory();
        $body = Pager::fromRequest($history, $request);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $body,
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Patch.
     */
    public function patch(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection, string $object): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();
        $namespace = $this->namespace_factory->getOne($namespace);
        $collection = $namespace->getCollection($collection);
        $object = $collection->getObject(['name' => $object]);
        $doc = ['data' => $object->getData()];
        $patch = new Patch(json_encode($doc), json_encode($body));
        $patched = $patch->apply();
        $update = json_decode($patched, true);

        $this->object_factory->update($collection, $object, $update);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $collection->getObject(['_id' => $object->getId()])->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Delete.
     */
    public function delete(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection, string $object): ResponseInterface
    {
        $collection = $this->namespace_factory->getOne($namespace)->getCollection($collection);
        $this->object_factory->deleteOne($collection, $object);

        return(new Response())->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);
    }

    /**
     * Entrypoint.
     */
    public function getAllLogs(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection, string $object): ResponseInterface
    {
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
        ], $request->getQueryParams());

        if (isset($query['watch'])) {
            $filter = [
                'fullDocument.context.namespace' => $namespace,
                'fullDocument.context.collection' => $collection,
                'fullDocument.context.object' => $object,
            ];

            if (!empty($query['query'])) {
                $filter = ['$and' => [$filter, $query['query']]];
            }

            $logs = $this->log_factory->watch(null, true, $filter, (int) $query['offset'], (int) $query['limit'], $query['sort']);

            return Helper::watchAll($request, $identity, $this->acl, $logs);
        }

        $filter = [
            'context.namespace' => $namespace,
            'context.collection' => $collection,
            'context.object' => $object,
        ];

        if (!empty($query['query'])) {
            $filter = ['$and' => [$filter, $query['query']]];
        }

        $logs = $this->log_factory->getAll($filter, (int) $query['offset'], (int) $query['limit'], $query['sort']);

        return Helper::getAll($request, $identity, $this->acl, $logs);
    }

    /**
     * Entrypoint.
     */
    public function getOneLog(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection, string $object, ObjectIdInterface $log): ResponseInterface
    {
        $resource = $this->log_factory->getOne($log);

        return Helper::getOne($request, $identity, $resource);
    }
}
