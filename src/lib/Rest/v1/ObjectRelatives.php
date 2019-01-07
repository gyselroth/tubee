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
use MongoDB\BSON\ObjectId;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tubee\Acl;
use Tubee\DataObjectRelation\Factory as DataObjectRelationFactory;
use Tubee\ResourceNamespace\Factory as ResourceNamespaceFactory;
use Tubee\Rest\Helper;
use Zend\Diactoros\Response;

class ObjectRelatives
{
    /**
     * namespace factory.
     *
     * @var ResourceNamespaceFactory
     */
    protected $namespace_factory;

    /**
     * Dataobject relation factory.
     *
     * @var DataObjectRelationFactory
     */
    protected $relation_factory;

    /**
     * Acl.
     *
     * @var Acl
     */
    protected $acl;

    /**
     * Init.
     */
    public function __construct(ResourceNamespaceFactory $namespace_factory, DataObjectRelationFactory $relation_factory, Acl $acl)
    {
        $this->namespace_factory = $namespace_factory;
        $this->relation_factory = $relation_factory;
        $this->acl = $acl;
    }

    /**
     * Entrypoint.
     */
    public function getAll(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection, ObjectId $object): ResponseInterface
    {
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
        ], $request->getQueryParams());

        $collection = $this->namespace_factory->getOne($namespace)->getCollection($collection);
        $object = $collection->getObject(['_id' => $object]);
        $relatives = $object->getRelatives($query['query'], false, (int) $query['offset'], (int) $query['limit'], $query['sort']);

        return Helper::getAll($request, $identity, $this->acl, $relatives);
    }

    /**
     * Entrypoint.
     */
    public function getOne(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection, ObjectId $object, ObjectId $relative): ResponseInterface
    {
        $collection = $this->namespace_factory->getOne($namespace)->getCollection($collection);
        $object = $collection->getObject(['_id' => $object], false);
        $relative = $object->getRelative($relative);

        return Helper::getOne($request, $identity, $relative);
    }

    /**
     * Create object.
     */
    public function post(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection, ObjectId $object): ResponseInterface
    {
        $query = array_merge([
            'write' => false,
        ], $request->getQueryParams());

        $body = array_merge([
            'data' => [],
            'endpoints' => null,
        ], $request->getParsedBody());

        $collection = $this->namespace_factory->getOne($namespace)->getCollection($collection);
        $id = $collection->createObject($body['data'], false, $body['endpoints']);

        if ($query['write'] === true) {
            //add job
        }

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_CREATED),
            $collection->getOne(['_id' => $id], false)->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Watch.
     */
    public function watchAll(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection, ObjectId $id): ResponseInterface
    {
        $query = array_merge([
            'offset' => null,
            'limit' => null,
            'existing' => true,
        ], $request->getQueryParams());

        $object = $this->namespace_factory->getOne($namespace)->getCollection($collection)->getObject($id);
        $cursor = $this->relation_factory->watch($object, $query['query'], $query['offset'], $query['limit'], $query['sort']);

        return Helper::watchAll($request, $identity, $this->acl, $cursor);
    }
}
