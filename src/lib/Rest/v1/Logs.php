<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Rest\v1;

use Micro\Auth\Identity;
use MongoDB\BSON\ObjectId;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tubee\Acl;
use Tubee\Job\Factory as JobFactory;
use Tubee\Log\Factory as LogFactory;
use Tubee\Process\Factory as ProcessFactory;
use Tubee\ResourceNamespace\Factory as ResourceNamespaceFactory;
use Tubee\Rest\Helper;

class Logs
{
    /**
     * Namespace factory.
     *
     * @var ResourceNamespaceFactory
     */
    protected $namespace_factory;

    /**
     * Log factory.
     *
     * @var LogFactory
     */
    protected $log_factory;

    /**
     * Job factory.
     *
     * @var JobFactory
     */
    protected $job_factory;

    /**
     * Process factory.
     *
     * @var ProcessFactory
     */
    protected $process_factory;

    /**
     * Acl.
     *
     * @var Acl
     */
    protected $acl;

    /**
     * Init.
     */
    public function __construct(JobFactory $job_factory, ResourceNamespaceFactory $namespace_factory, Acl $acl, ProcessFactory $process_factory, LogFactory $log_factory)
    {
        $this->namespace_factory = $namespace_factory;
        $this->job_factory = $job_factory;
        $this->process_factory = $process_factory;
        $this->log_factory = $log_factory;
        $this->acl = $acl;
    }

    /**
     * Get all.
     */
    public function getAll(ServerRequestInterface $request, Identity $identity, string $namespace, ?string $job = null, ?ObjectId $process = null): ResponseInterface
    {
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
        ], $request->getQueryParams());

        $namespace = $this->namespace_factory->getOne($namespace);

        if ($process !== null) {
            $resource = $this->process_factory->getOne($namespace, $process);
        } else {
            $resource = $this->job_factory->getOne($namespace, $job);
        }

        $logs = $resource->getLogs($query['query'], $query['offset'], $query['limit'], $query['sort']);

        return Helper::getAll($request, $identity, $this->acl, $logs);
    }

    /**
     * Get one.
     */
    public function getOne(ServerRequestInterface $request, Identity $identity, string $namespace, ?string $job = null, ?ObjectId $process = null, ObjectId $log): ResponseInterface
    {
        $namespace = $this->namespace_factory->getOne($namespace);

        if ($process !== null) {
            $resource = $this->process_factory->getOne($namespace, $process)->getLog($log);
        } else {
            $resource = $this->job_factory->getOne($namespace, $job)->getLog($log);
        }

        return Helper::getOne($request, $identity, $resource);
    }

    /**
     * Watch all.
     */
    public function watchAll(ServerRequestInterface $request, Identity $identity, string $namespace, string $job = null, ?ObjectId $process = null): ResponseInterface
    {
        $namespace = $this->namespace_factory->getOne($namespace);

        $query = array_merge([
            'offset' => null,
            'limit' => null,
            'existing' => true,
        ], $request->getQueryParams());

        if ($process !== null) {
            $process = $this->process_factory->getOne($namespace, $process);
            $query['query']['$or'][] = ['fullDocument.context.process' => (string) $process->getId()];
            $query['query']['$or'][] = ['fullDocument.context.parent' => (string) $process->getId()];
        } else {
            $job = $this->job_factory->getOne($namespace, $job);
            $query['query']['fullDocument.context.job'] = (string) $job->getId();
        }

        $cursor = $this->log_factory->watch(null, true, $query['query'], $query['offset'], $query['limit'], $query['sort']);

        return Helper::watchAll($request, $identity, $this->acl, $cursor);
    }
}
