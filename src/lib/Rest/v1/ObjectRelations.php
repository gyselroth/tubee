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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rs\Json\Patch;
use Tubee\Acl;
use Tubee\DataObjectRelation\Factory as DataObjectRelationFactory;
use Tubee\ResourceNamespace\Factory as ResourceNamespaceFactory;
use Tubee\Rest\Helper;
use Zend\Diactoros\Response;

class ObjectRelations
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
    public function getAll(ServerRequestInterface $request, Identity $identity, string $namespace, ?string $collection = null, ?string $object = null): ResponseInterface
    {
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
        ], $request->getQueryParams());

        $namespace = $this->namespace_factory->getOne($namespace);

        if ($object !== null) {
            $collection = $namespace->getCollection($collection);
            $object = $collection->getObject(['name' => $object]);
            $relatives = $object->getRelations($query['query'], false, (int) $query['offset'], (int) $query['limit'], $query['sort']);

            return Helper::getAll($request, $identity, $this->acl, $relatives);
        }

        if (isset($query['watch'])) {
            $cursor = $this->relation_factory->watch($namespace, null, true, $query['query'], $query['offset'], $query['limit'], $query['sort']);

            return Helper::watchAll($request, $identity, $this->acl, $cursor);
        }

        $result = $this->relation_factory->getAll($namespace, $query['query'], (int) $query['offset'], (int) $query['limit'], $query['sort']);

        return Helper::getAll($request, $identity, $this->acl, $result);
    }

    /**
     * Entrypoint.
     */
    public function getOne(ServerRequestInterface $request, Identity $identity, string $namespace, ?string $collection = null, ?string $object = null, ?string $relation = null): ResponseInterface
    {
        if ($object != null) {
            $collection = $this->namespace_factory->getOne($namespace)->getCollection($collection);
            $object = $collection->getObject(['name' => $object], false);
            $relative = $object->getRelation($relation);

            return Helper::getOne($request, $identity, $relative);
        }

        $namespace = $this->namespace_factory->getOne($namespace);
        $relative = $this->relation_factory->getOne($namespace, $relation);

        return Helper::getOne($request, $identity, $relative);
    }

    /**
     * Create object.
     */
    public function post(ServerRequestInterface $request, Identity $identity, string $namespace): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();

        $namespace = $this->namespace_factory->getOne($namespace);
        $this->relation_factory->add($namespace, $body);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_CREATED),
            $this->relation_factory->getOne($namespace, $body['name'])->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Delete.
     */
    public function delete(ServerRequestInterface $request, Identity $identity, string $namespace, string $relation): ResponseInterface
    {
        $collection = $this->namespace_factory->getOne($namespace);
        $this->relation_factory->deleteOne($namespace, $relation);

        return(new Response())->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);
    }

    /**
     * Patch.
     */
    public function patch(ServerRequestInterface $request, Identity $identity, string $namespace, string $relation): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();
        $namespace = $this->namespace_factory->getOne($namespace);
        $relation = $this->relation_factory->getOne($namespace, $relation);
        $doc = ['data' => $relation->getData()];

        $patch = new Patch(json_encode($doc), json_encode($body));
        $patched = $patch->apply();
        $update = json_decode($patched, true);

        $this->relation_factory->update($relation, $update);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $this->relation_factory->getOne($namespace, $relation->getName())->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }
}
