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
use MongoDB\BSON\ObjectId;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TaskScheduler\Scheduler;
use Tubee\Acl;
use Tubee\Job;
use Tubee\Job\Factory as JobFactory;
use Tubee\Mandator\Factory as MandatorFactory;
use Tubee\Rest\Pager;
use Zend\Diactoros\Response;

class Jobs
{
    /**
     * Init.
     */
    public function __construct(JobFactory $job_factory, Acl $acl, MandatorFactory $mandator_factory)
    {
        $this->job_factory = $job_factory;
        $this->acl = $acl;
        $this->mandator_factory = $mandator_factory;
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

        $jobs = $this->job_factory->getAll($query['query'], $query['offset'], $query['limit']);
        $body = $this->acl->filterOutput($request, $identity, $jobs);
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
    public function getOne(ServerRequestInterface $request, Identity $identity, ObjectId $job): ResponseInterface
    {
        $query = $request->getQueryParams();

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $this->job_factory->getOne($job)->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Delete job.
     */
    public function delete(ServerRequestInterface $request, Identity $identity, ObjectId $job): ResponseInterface
    {
        $this->job_factory->deleteOne($job);

        return (new Response())->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);
    }

    /**
     * Add new job.
     */
    public function post(ServerRequestInterface $request, Identity $identity): ResponseInterface
    {
        $job = [
            //'action' => $action,
            'mandator' => [],
            'datatypes' => [],
            'filter' => [],
            'endpoints' => [],
            'simulate' => false,
            'ignore' => true,
            'options' => [
                Scheduler::OPTION_AT => 0,
                Scheduler::OPTION_INTERVAL => 0,
                Scheduler::OPTION_RETRY => 0,
                Scheduler::OPTION_RETRY_INTERVAL => 0,
                Scheduler::OPTION_TIMEOUT => 0,
            ],
        ];

        $body = $request->getParsedBody();
        $query = $request->getQueryParams();
        $job = array_merge($job, $body);

        //validate job requst
        $this->mandator_factory->getAll($job['mandator']);
        $id = $this->job_factory->create($job);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_ACCEPTED),
            $this->job_factory->getOne($id)->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }
}
