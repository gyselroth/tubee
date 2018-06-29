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
use Tubee\Manager;
use Tubee\Rest\Pager;
use Zend\Diactoros\Response;

class Mandators
{
    /**
     * Init.
     */
    public function __construct(Manager $manager, Acl $acl)
    {
        $this->manager = $manager;
        $this->acl = $acl;
    }

    /**
     * Entrypoint.
     */
    public function get(ServerRequestInterface $request, Identity $identity, ?string $mandator = null): ResponseInterface
    {
        $query = array_merge([
            'query' => [],
        ], $request->getQueryParams());

        if ($mandator !== null) {
            return new UnformattedResponse(
                (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
                $this->manager->getMandator($mandator)->decorate($request),
                ['pretty' => isset($query['pretty'])]
            );
        }

        $mandators = $this->manager->getMandators($query['query']);
        $body = $this->acl->filterOutput($request, $identity, $mandators);
        $body = Pager::fromRequest($body, $request);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $body,
            ['pretty' => isset($query['pretty'])]
        );
    }
}
