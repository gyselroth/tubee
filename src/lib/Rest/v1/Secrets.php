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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rs\Json\Patch;
use Tubee\Acl;
use Tubee\ResourceNamespace\Factory as ResourceNamespaceFactory;
use Tubee\Rest\Helper;
use Tubee\Secret;
use Tubee\Secret\Factory as SecretFactory;
use Zend\Diactoros\Response;

class Secrets
{
    /**
     * Secret factory.
     *
     * @var SecretFactory
     */
    protected $secret_factory;

    /**
     * Acl.
     *
     * @var Acl
     */
    protected $acl;

    /**
     * namespace factory.
     *
     * @var ResourceNamespaceFactory
     */
    protected $namespace_factory;

    /**
     * Init.
     */
    public function __construct(SecretFactory $secret_factory, Acl $acl, ResourceNamespaceFactory $namespace_factory)
    {
        $this->secret_factory = $secret_factory;
        $this->acl = $acl;
        $this->namespace_factory = $namespace_factory;
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

        if (isset($query['watch']) && !empty($query['watch'])) {
            $cursor = $this->secret_factory->watch($namespace, null, true, $query['query'], (int) $query['offset'], (int) $query['limit'], $query['sort']);

            return Helper::watchAll($request, $identity, $this->acl, $cursor);
        }

        $secrets = $this->secret_factory->getAll($namespace, $query['query'], $query['offset'], $query['limit'], $query['sort']);

        return Helper::getAll($request, $identity, $this->acl, $secrets);
    }

    /**
     * Entrypoint.
     */
    public function getOne(ServerRequestInterface $request, Identity $identity, string $namespace, string $secret): ResponseInterface
    {
        $namespace = $this->namespace_factory->getOne($namespace);
        $resource = $this->secret_factory->getOne($namespace, $secret);

        return Helper::getOne($request, $identity, $resource);
    }

    /**
     * Delete secret.
     */
    public function delete(ServerRequestInterface $request, Identity $identity, string $namespace, string $secret): ResponseInterface
    {
        $namespace = $this->namespace_factory->getOne($namespace);
        $this->secret_factory->deleteOne($namespace, $secret);

        return (new Response())->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);
    }

    /**
     * Add new secret.
     */
    public function post(ServerRequestInterface $request, Identity $identity, string $namespace): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();

        $namespace = $this->namespace_factory->getOne($namespace);
        $this->secret_factory->add($namespace, $body);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_CREATED),
            $this->secret_factory->getOne($body['name'])->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Patch.
     */
    public function patch(ServerRequestInterface $request, Identity $identity, string $namespace, string $secret): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();
        $namespace = $this->namespace_factory->getOne($namespace);

        $secret = $this->secret_factory->getOne($namespace, $secret);
        $doc = ['data' => $secret->getData()];

        $patch = new Patch(json_encode($doc), json_encode($body));
        $patched = $patch->apply();
        $update = json_decode($patched, true);
        $this->secret_factory->update($secret, $update);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $this->secret_factory->getOne($namespace, $secret->getName())->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }
}
