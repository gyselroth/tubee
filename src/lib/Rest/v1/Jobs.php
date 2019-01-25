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
use MongoDB\BSON\ObjectIdInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rs\Json\Patch;
use Tubee\Acl;
use Tubee\Job;
use Tubee\Job\Factory as JobFactory;
use Tubee\Log\Factory as LogFactory;
use Tubee\ResourceNamespace\Factory as ResourceNamespaceFactory;
use Tubee\Rest\Helper;
use Zend\Diactoros\Response;

class Jobs
{
    /**
     * namespace factory.
     *
     * @var ResourceNamespaceFactory
     */
    protected $namespace_factory;

    /**
     * Job factory.
     *
     * @var JobFactory
     */
    protected $job_factory;

    /**
     * Acl.
     *
     * @var Acl
     */
    protected $acl;

    /**
     * Log factory.
     *
     * @var LogFactory
     */
    protected $log_factory;

    /**
     * Init.
     */
    public function __construct(JobFactory $job_factory, Acl $acl, ResourceNamespaceFactory $namespace_factory, LogFactory $log_factory)
    {
        $this->job_factory = $job_factory;
        $this->acl = $acl;
        $this->namespace_factory = $namespace_factory;
        $this->log_factory = $log_factory;
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

        if (isset($query['watch'])) {
            $cursor = $this->job_factory->watch($namespace, null, true, $query['query'], (int) $query['offset'], (int) $query['limit'], $query['sort']);

            return Helper::watchAll($request, $identity, $this->acl, $cursor);
        }

        $jobs = $this->job_factory->getAll($namespace, $query['query'], $query['offset'], $query['limit'], $query['sort']);

        return Helper::getAll($request, $identity, $this->acl, $jobs);
    }

    /**
     * Entrypoint.
     */
    public function getOne(ServerRequestInterface $request, Identity $identity, string $namespace, string $job): ResponseInterface
    {
        $namespace = $this->namespace_factory->getOne($namespace);
        $resource = $this->job_factory->getOne($namespace, $job);

        return Helper::getOne($request, $identity, $resource);
    }

    /**
     * Delete job.
     */
    public function delete(ServerRequestInterface $request, Identity $identity, string $namespace, string $job): ResponseInterface
    {
        $namespace = $this->namespace_factory->getOne($namespace);
        $this->job_factory->deleteOne($namespace, $job);

        return (new Response())->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);
    }

    /**
     * Add new job.
     */
    public function post(ServerRequestInterface $request, Identity $identity, string $namespace): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();
        $job = array_merge(['namespaces' => []], $body);

        $namespace = $this->namespace_factory->getOne($namespace);
        $this->job_factory->create($namespace, $job);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_ACCEPTED),
            $this->job_factory->getOne($namespace, $job['name'])->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Patch.
     */
    public function patch(ServerRequestInterface $request, Identity $identity, string $namespace, string $job): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();

        $namespace = $this->namespace_factory->getOne($namespace);
        $job = $this->job_factory->getOne($namespace, $job);
        $doc = ['data' => $job->getData()];

        $patch = new Patch(json_encode($doc), json_encode($body));
        $patched = $patch->apply();
        $update = json_decode($patched, true);
        $this->job_factory->update($job, $update);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $this->job_factory->getOne($namespace, $job->getName())->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Entrypoint.
     */
    public function getAllLogs(ServerRequestInterface $request, Identity $identity, string $namespace, string $job): ResponseInterface
    {
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
        ], $request->getQueryParams());

        if (isset($query['watch'])) {
            $filter = [
                'fullDocument.context.namespace' => $namespace,
                'fullDocument.context.job' => $job,
            ];

            if (!empty($query['query'])) {
                $filter = ['$and' => [$filter, $query['query']]];
            }

            $logs = $this->log_factory->watch(null, true, $filter, (int) $query['offset'], (int) $query['limit'], $query['sort']);

            return Helper::watchAll($request, $identity, $this->acl, $logs);
        }

        $filter = [
            'context.namespace' => $namespace,
            'context.job' => $job,
        ];

        if (!empty($query['query'])) {
            $filter = ['$and' => [$filter, $query['query']]];
        }

        $logs = $this->log_factory->getAll($filter, (int) $query['offset'], (int) $query['limit'], $query['sort']);

        return Helper::getAll($request, $identity, $this->acl, $logs);
    }

    /**
     * Entrypoint.
     */
    public function getOneLog(ServerRequestInterface $request, Identity $identity, string $namespace, string $job, ObjectIdInterface $log): ResponseInterface
    {
        $resource = $this->log_factory->getOne($log);

        return Helper::getOne($request, $identity, $resource);
    }
}
