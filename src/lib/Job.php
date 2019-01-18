<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use DateTime;
use Generator;
use MongoDB\BSON\ObjectIdInterface;
use Psr\Http\Message\ServerRequestInterface;
use TaskScheduler\JobInterface as TaskJobInterface;
use TaskScheduler\Process;
use TaskScheduler\Scheduler;
use Tubee\Job\JobInterface;
use Tubee\Log\Factory as LogFactory;
use Tubee\Log\LogInterface;
use Tubee\Process\Factory as ProcessFactory;
use Tubee\Process\ProcessInterface;
use Tubee\Resource\AbstractResource;
use Tubee\Resource\AttributeResolver;
use Tubee\ResourceNamespace\ResourceNamespaceInterface;

class Job extends AbstractResource implements JobInterface
{
    /**
     * Namespace.
     *
     * @var ResourceNamespaceInterface
     */
    protected $namespace;

    /**
     * Process factory.
     *
     * @var ProcessFactory
     */
    protected $process_factory;

    /**
     * Log factory.
     *
     * @var LogFactory
     */
    protected $log_factory;

    /**
     * Taskscheduler.
     *
     * @var Scheduler
     */
    protected $scheduler;

    /**
     * Data object.
     */
    public function __construct(array $resource, ResourceNamespaceInterface $namespace, Scheduler $scheduler, ProcessFactory $process_factory, LogFactory $log_factory)
    {
        $this->resource = $resource;
        $this->namespace = $namespace;
        $this->process_factory = $process_factory;
        $this->log_factory = $log_factory;
        $this->scheduler = $scheduler;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(ServerRequestInterface $request): array
    {
        $resource = $this;
        $scheduler = $this->scheduler;

        $result = [
            '_links' => [
                'namespace' => ['href' => (string) $request->getUri()->withPath('/api/v1/namespaces/'.$this->namespace->getName())],
            ],
            'kind' => 'Job',
            'namespace' => $this->namespace->getName(),
            'data' => $this->getData(),
            'status' => function () use ($resource, $scheduler) {
                $process = iterator_to_array($scheduler->getJobs([
                    'data.job' => $resource->getName(),
                    'data.parent' => ['$exists' => false],
                ]));

                $process = end($process);

                if ($process === false) {
                    return [
                        'status' => false,
                    ];
                }

                $process = $process->toArray();

                return [
                    'status' => true,
                    'last_process' => [
                        'process' => (string) $process['_id'],
                        'next' => $process['options']['at'] === 0 ? null : (new DateTime('@'.(string) $process['options']['at']))->format('c'),
                        'started' => $process['status'] === 0 ? null : $process['started']->toDateTime()->format('c'),
                        'ended' => $process['status'] <= 2 ? null : $process['ended']->toDateTime()->format('c'),
                        'result' => TaskJobInterface::STATUS_MAP[$process['status']],
                        'code' => $process['status'],
                    ],
                ];
            },
        ];

        return AttributeResolver::resolve($request, $this, $result);
    }

    /**
     * {@inheritdoc}
     */
    public function getLogs(array $query = [], ?int $offset = null, ?int $limit = null, ?array $sort = []): Generator
    {
        $query['context.job'] = (string) $this->getId();

        return $this->log_factory->getAll($query, $offset, $limit, $sort);
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceNamespace(): ResourceNamespaceInterface
    {
        return $this->namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function getLog(ObjectIdInterface $id): LogInterface
    {
        return $this->log_factory->getOne($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getProcesses(array $query = [], ?int $offset = null, ?int $limit = null, array $sort = []): Generator
    {
        $query['job'] = $this->getId();

        return $this->process_factory->getAll($this->namespace, $query, $offset, $limit, $sort);
    }

    /**
     * {@inheritdoc}
     */
    public function getProcess(ObjectIdInterface $id): ProcessInterface
    {
        return $this->process_factory->getOne($this->namespace, $id);
    }
}
