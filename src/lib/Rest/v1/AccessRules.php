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
use Tubee\AccessRule\Factory as AccessRuleFactory;
use Tubee\Acl;
use Tubee\Rest\Helper;
use Zend\Diactoros\Response;

class AccessRules
{
    /**
     * rule factory.
     *
     * @var AccessRuleFactory
     */
    protected $rule_factory;

    /**
     * Acl.
     *
     * @var Acl
     */
    protected $acl;

    /**
     * Init.
     */
    public function __construct(AccessRuleFactory $rule_factory, Acl $acl)
    {
        $this->rule_factory = $rule_factory;
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
            $cursor = $this->rule_factory->watch(null, true, $query['query'], $query['offset'], $query['limit'], $query['sort']);

            return Helper::watchAll($request, $identity, $this->acl, $cursor);
        }

        $rules = $this->rule_factory->getAll($query['query'], $query['offset'], $query['limit'], $query['sort']);

        return Helper::getAll($request, $identity, $this->acl, $rules);
    }

    /**
     * Entrypoint.
     */
    public function getOne(ServerRequestInterface $request, Identity $identity, string $rule): ResponseInterface
    {
        $resource = $this->rule_factory->getOne($rule);

        return Helper::getOne($request, $identity, $resource);
    }

    /**
     * Add new access rule.
     */
    public function post(ServerRequestInterface $request, Identity $identity): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();
        $id = $this->rule_factory->add($body);
        $rule = $this->rule_factory->getOne($body['name']);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_CREATED),
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
        $query = $request->getQueryParams();

        if ($this->rule_factory->has($rule)) {
            $this->rule_factory->update($rule, $body);
            $rule = $this->rule_factory->getOne($rule);

            return new UnformattedResponse(
                (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
                $rule->decorate($request),
                ['pretty' => isset($query['pretty'])]
            );
        }

        $body['name'] = $rule;
        $id = $this->rule_factory->add($body);
        $rule = $this->rule_factory->getOne($body['name']);

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
        $this->rule_factory->deleteOne($rule);

        return (new Response())->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);
    }

    /**
     * Patch.
     */
    public function patch(ServerRequestInterface $request, Identity $identity, string $rule): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();
        $rule = $this->rule_factory->getOne($rule);
        $doc = ['data' => $rule->getData()];

        $patch = new Patch(json_encode($doc), json_encode($body));
        $patched = $patch->apply();
        $update = json_decode($patched, true);

        $this->rule_factory->update($rule, $update);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $this->rule_factory->getOne($rule->getName())->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }
}
