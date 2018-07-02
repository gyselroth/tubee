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
use Tubee\Acl;
use Tubee\Rest\Pager;
use Zend\Diactoros\Response;

class AccessRules
{
    /**
     * Init.
     */
    public function __construct(Acl $acl)
    {
        $this->acl = $acl;
    }

    /**
     * Entrypoint.
     */
    public function get(ServerRequestInterface $request, Identity $identity, ?string $rule = null): ResponseInterface
    {
        $query = array_merge([
            'query' => [],
        ], $request->getQueryParams());

        if ($rule !== null) {
            return new UnformattedResponse(
                (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
                $this->acl->getRule($rule)->decorate($request),
                ['pretty' => isset($query['pretty'])]
            );
        }

        $rules = $this->acl->getRules($query['query']);

        $body = $this->acl->filterOutput($request, $identity, $rules);
        $body = Pager::fromRequest($body, $request);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $body,
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Add new access rule.
     */
    public function post(ServerRequestInterface $request, Identity $identity): ResponseInterface
    {
        $body = $request->getDecodedBody();
        $id = $this->acl->addRule($body);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_CREATED),
            $body,
            ['pretty' => isset($query['pretty'])]
        );
    }
}
