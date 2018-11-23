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
use Psr\Log\LoggerInterface;
use Rs\Json\Patch;
use Tubee\AccessRole\Factory as AccessRoleFactory;
use Tubee\Acl;
use Tubee\Rest\Helper;
use Zend\Diactoros\Response;

class AccessRoles
{
    /**
     * role factory.
     *
     * @var AccessRoleFactory
     */
    protected $role_factory;

    /**
     * Acl.
     *
     * @var Acl
     */
    protected $acl;

    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Init.
     */
    public function __construct(AccessRoleFactory $role_factory, Acl $acl, LoggerInterface $logger)
    {
        $this->role_factory = $role_factory;
        $this->acl = $acl;
        $this->logger = $logger;
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

        $roles = $this->role_factory->getAll($query['query'], $query['offset'], $query['limit'], $query['sort']);

        return Helper::getAll($request, $identity, $this->acl, $roles);
    }

    /**
     * Entrypoint.
     */
    public function getOne(ServerRequestInterface $request, Identity $identity, string $role): ResponseInterface
    {
        $resource = $this->role_factory->getOne($role);

        return Helper::getOne($request, $identity, $resource);
    }

    /**
     * Add new access role.
     */
    public function post(ServerRequestInterface $request, Identity $identity): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();
        $id = $this->role_factory->add($body);
        $role = $this->role_factory->getOne($body['name']);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_CREATED),
            $role->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Create or replace access role.
     */
    public function put(ServerRequestInterface $request, Identity $identity, string $role): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();

        if ($this->role_factory->has()) {
            $this->role_factory->update($role, $body);
            $role = $this->role_factory->getOne($role);

            return new UnformattedResponse(
                (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
                $role->decorate($request),
                ['pretty' => isset($query['pretty'])]
            );
        }

        $body['name'] = $role;
        $id = $this->role_factory->add($body);
        $role = $this->role_factory->getOne($body['name']);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_CREATED),
            $role->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Delete access role.
     */
    public function delete(ServerRequestInterface $request, Identity $identity, string $role): ResponseInterface
    {
        $this->role_factory->deleteOne($role);

        return (new Response())->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);
    }

    /**
     * Patch.
     */
    public function patch(ServerRequestInterface $request, Identity $identity, string $role): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();
        $role = $this->role_factory->getOne($role);
        $doc = ['data' => $role->getData()];

        $patch = new Patch(json_encode($doc), json_encode($body));
        $patched = $patch->apply();
        $update = json_decode($patched, true);
        $this->role_factory->update($role, $update);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $this->role_factory->getOne($role->getName())->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Watch.
     */
    public function watchAll(ServerRequestInterface $request, Identity $identity): ResponseInterface
    {
        $query = array_merge([
            'offset' => null,
            'limit' => null,
            'existing' => true,
        ], $request->getQueryParams());

        $cursor = $this->role_factory->watch(null, true, $query['query'], $query['offset'], $query['limit'], $query['sort']);

        return Helper::watchAll($request, $identity, $this->acl, $cursor);
    }
}
