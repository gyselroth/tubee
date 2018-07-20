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
use Tubee\AccessRule\Factory as AccessRuleFactory;
use Tubee\Acl;
use Tubee\Rest\Pager;
use Zend\Diactoros\Response;

class AccessRules
{
    /**
     * Init.
     */
    public function __construct(AccessRuleFactory $rule, Acl $acl)
    {
        $this->rule = $rule;
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
            'query' => [],
        ], $request->getQueryParams());

        $rules = $this->rule->getAll($query['query'], $query['offset'], $query['limit']);

        $body = $this->acl->filterOutput($request, $identity, $rules);
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
    public function getOne(ServerRequestInterface $request, Identity $identity, string $rule): ResponseInterface
    {
        $request->getQueryParams();

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $this->rule->getOne($rule)->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Add new access rule.
     */
    public function post(ServerRequestInterface $request, Identity $identity): ResponseInterface
    {
        $body = $request->getParsedBody();
        $id = $this->rule->add($body);
        $rule = $this->rule->getOne($body['name']);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_CREATED),
            $rule->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Update access rule.
     */
    public function patch(ServerRequestInterface $request, Identity $identity, string $rule): ResponseInterface
    {
        $body = $request->getParsedBody();

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $rule->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Create or replace access rule.
     */
    public function put(ServerRequestInterface $request, Identity $identity, string $rule): ResponseInterface
    {
        $body = $request->getParsedBody();

        if ($this->rule->has($rule)) {
            $this->rule->update($rule, $body);
            $rule = $this->rule->getOne($rule);

            return new UnformattedResponse(
                (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
                $rule->decorate($request),
                ['pretty' => isset($query['pretty'])]
            );
        }

        $body['name'] = $rule;
        $id = $this->rule->add($body);
        $rule = $this->rule->getOne($body['name']);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_CREATED),
            $rule->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Delete access rule.
     */
    public function delete(ServerRequestInterface $request, Identity $identity, string $rule): ResponseInterface
    {
        $body = $request->getParsedBody();
        $this->rule->delete($rule);

        return (new Response())->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);
    }
}
