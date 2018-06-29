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
use TaskScheduler\Scheduler;
use Tubee\Acl;
use Tubee\Rest\Pager;
use Zend\Diactoros\Response;

class Jobs
{
    /**
     * Init.
     */
    public function __construct(Scheduler $scheduler, Acl $acl)
    {
        $this->scheduler = $scheduler;
        $this->acl = $acl;
    }

    /**
     * Entrypoint.
     */
    public function get(ServerRequestInterface $request, Identity $identity, ?string $job = null): ResponseInterface
    {
        $query = array_merge([
            'query' => [],
        ], $request->getQueryParams());

        if ($job !== null) {
            return new UnformattedResponse(
                (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
                $this->manager->getMandator($mandator)->decorate($request),
                ['pretty' => isset($query['pretty'])]
            );
        }

        $jobs = $this->scheduler->getJobs();
        $body = $this->acl->filterOutput($request, $identity, $jobs);
        $body = Pager::fromRequest($body, $request, function ($object, $request) {
            return [
                'kind' => 'Job',
            ];
        });

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $body,
            ['pretty' => isset($query['pretty'])]
        );
    }
}
