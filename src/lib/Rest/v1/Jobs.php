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
use Tubee\Async\Sync as SyncJob;
use Tubee\Job;
use Tubee\JobManager;
use Tubee\MandatorManager;
use Tubee\Rest\Pager;
use Zend\Diactoros\Response;

class Jobs
{
    /**
     * Init.
     */
    public function __construct(JobManager $scheduler, Acl $acl, MandatorManager $manager)
    {
        $this->scheduler = $scheduler;
        $this->acl = $acl;
        $this->manager = $manager;
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

        $jobs = $this->scheduler->getTasks($query['query'], $query['offset'], $query['limit']);
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
            $this->scheduler->getTask($job)->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Delete job.
     */
    public function delete(ServerRequestInterface $request, Identity $identity, ObjectId $job): ResponseInterface
    {
        $this->scheduler->cancelJob($job);

        return (new Response())->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);
    }

    /**
     * Get errors.
     */
    public function getErrors(ServerRequestInterface $request, Identity $identity, ObjectId $job, ?ObjectId $error = null): ResponseInterface
    {
        exit();
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
            'query' => [],
        ], $request->getQueryParams());

        if ($error !== null) {
            return new UnformattedResponse(
                (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
                $this->scheduler->getError($error)->decorate($request),
                ['pretty' => isset($query['pretty'])]
            );
        }

        $errors = $this->scheduler->getErrors($job, $query['query'], $query['offset'], $query['limit']);
        $body = $this->acl->filterOutput($request, $identity, $errors);
        $body = Pager::fromRequest($body, $request);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $body,
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Watch errors.
     */
    public function watchErrors(ServerRequestInterface $request, Identity $identity, ObjectId $job): ResponseInterface
    {
    }

    /**
     * Add new job.
     */
    public function post(ServerRequestInterface $request, Identity $identity): ResponseInterface
    {
        $job = [
            'action' => $action,
            'mandator' => [],
            'datatypes' => [],
            'filter' => [],
            'endpoints' => [],
            'simulate' => false,
            'ignore' => true,
        ];

        $body = $request->getParsedBody();
        $query = $request->getQueryParams();

        $task = [
            Scheduler::OPTION_AT => 'task-at',
            Scheduler::OPTION_INTERVAL => 'task-interval',
            Scheduler::OPTION_RETRY_INTERVAL => 'task-retry-interval',
            Scheduler::OPTION_RETRY => 'task-retry',
        ];
        $task_set = array_intersect_key($body, array_flip($task));
        $task_set = array_combine(array_intersect_key($task, $task_set), $task_set);
        $job = array_merge($job, $body);

        //validate job requst
        $this->manager->getMandators($job['mandator']);
        $id = $this->scheduler->addJob(SyncJob::class, $job, $task_set);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_ACCEPTED),
            $this->scheduler->getTask($id)->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }
}
