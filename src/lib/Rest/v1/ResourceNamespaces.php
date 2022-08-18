<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2022 gyselroth GmbH (https://gyselroth.com)
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
use Tubee\ResourceNamespace\Factory as ResourceNamespaceFactory;
use Tubee\Rest\Helper;
use Zend\Diactoros\Response;

class ResourceNamespaces
{
    /**
     * ResourceNamespace factory.
     *
     * @var ResourceNamespaceFactory
     */
    protected $namespace_factory;

    /**
     * Acl.
     *
     * @var Acl
     */
    protected $acl;

    /**
     * Init.
     */
    public function __construct(ResourceNamespaceFactory $namespace, Acl $acl)
    {
        $this->namespace_factory = $namespace;
        $this->acl = $acl;
    }

    /**
     * Entrypoint.
     */
    public function getAll(ServerRequestInterface $request, Identity $identity): ResponseInterface
    {
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
        ], $request->getQueryParams());

        if (isset($query['watch'])) {
            $cursor = $this->namespace_factory->watch(null, isset($query['stream']), $query['query'], $query['offset'], $query['limit'], $query['sort']);

            return Helper::watchAll($request, $identity, $this->acl, $cursor);
        }

        $namespaces = $this->namespace_factory->getAll($query['query'], (int) $query['offset'], (int) $query['limit'], $query['sort']);

        return Helper::getAll($request, $identity, $this->acl, $namespaces);
    }

    /**
     * Entrypoint.
     */
    public function getOne(ServerRequestInterface $request, Identity $identity, string $namespace): ResponseInterface
    {
        $resource = $this->namespace_factory->getOne($namespace);

        return Helper::getOne($request, $identity, $resource);
    }

    /**
     * Delete.
     */
    public function delete(ServerRequestInterface $request, Identity $identity, string $namespace): ResponseInterface
    {
        $this->namespace_factory->deleteOne($namespace);

        return (new Response())->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);
    }

    /**
     * Patch.
     */
    public function patch(ServerRequestInterface $request, Identity $identity, string $namespace): ResponseInterface
    {
        //there is nothing to patch for a resource namespace
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();
        $namespace = $this->namespace_factory->getOne($namespace);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $this->namespace_factory->getOne($namespace->getName())->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Create.
     */
    public function post(ServerRequestInterface $request, Identity $identity): ResponseInterface
    {
        $body = (array) $request->getParsedBody();
        $id = $this->namespace_factory->add($body);
        $query = $request->getQueryParams();

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_CREATED),
            $this->namespace_factory->getOne($body['name'])->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }
}
