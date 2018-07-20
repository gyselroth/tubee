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
use Tubee\AccessRole\Factory as AccessRoleFactory;
use Tubee\Acl;
use Tubee\Rest\Pager;
use Zend\Diactoros\Response;

class AccessRoles
{
    /**
     * Init.
     */
    public function __construct(AccessRoleFactory $role, Acl $acl)
    {
        $this->role = $role;
        $this->acl = $acl;
    }

    /**
     * Entrypoint.
     */
    public function get(ServerRequestInterface $request, Identity $identity): ResponseInterface
    {
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
            'query' => [],
        ], $request->getQueryParams());

        $roles = $this->role->getAll($query['query'], $query['offset'], $query['limit']);

        $body = $this->acl->filterOutput($request, $identity, $roles);
        $body = Pager::fromRequest($body, $request);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $body,
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Entrypoint.
     */
    public function getOne(ServerRequestInterface $request, Identity $identity, string $role): ResponseInterface
    {
        $query = $request->getQueryParams();

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $this->acl->getRule($role)->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Add new access role.
     */
    public function post(ServerRequestInterface $request, Identity $identity): ResponseInterface
    {
        $body = $request->getParsedBody();
        $id = $this->role->add($body);
        $role = $this->role->getOne($body['name']);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_CREATED),
            $role->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Update access role.
     */
    public function patch(ServerRequestInterface $request, Identity $identity, string $role): ResponseInterface
    {
        $body = $request->getParsedBody();

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
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

        if ($this->role->has()) {
            $this->role->update($role, $body);
            $role = $this->role->getOne($role);

            return new UnformattedResponse(
                (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
                $role->decorate($request),
                ['pretty' => isset($query['pretty'])]
            );
        }

        $body['name'] = $role;
        $id = $this->role->add($body);
        $role = $this->role->getOne($body['name']);

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
        $this->role->delete($role);

        return (new Response())->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);
    }
}
