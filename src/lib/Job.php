<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use Generator;
use MongoDB\BSON\ObjectIdInterface;
use Psr\Http\Message\ServerRequestInterface;
use TaskScheduler\Process;
use TaskScheduler\Scheduler;
use Tubee\Async\Sync;
use Tubee\Job\JobInterface;
use Tubee\Log\Factory as LogFactory;
use Tubee\Log\LogInterface;
use Tubee\Process\Factory as ProcessFactory;
use Tubee\Process\ProcessInterface;
use Tubee\Resource\AbstractResource;
use Tubee\Resource\AttributeResolver;

class Job extends AbstractResource implements JobInterface
{
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
    public function __construct(array $resource, Scheduler $scheduler, ProcessFactory $process_factory, LogFactory $log_factory)
    {
        $this->resource = $resource;
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
                'self' => ['href' => (string) $request->getUri()],
            ],
            'kind' => 'Job',
            'data' => $this->getData(),
            'status' => function () use ($resource, $scheduler) {
                $process = iterator_to_array($scheduler->getJobs([
                    'data.job' => $resource->getId(),
                ]));

                $process = end($process);
                if ($process === false) {
                    return [
                        'status' => false,
                    ];
                }

                return [
                    'status' => true,
                    'process' => (string) $process->getId(),
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
        return $this->log_factory->getAll($this, $query, $offset, $limit, $sort);
    }

    /**
     * Trigger job by force.
     */
    public function trigger(): Process
    {
        $resource = $this->resource['data'];
        $options = $this->resource['data']['options'];
        unset($options['at'], $options['interval']);

        $resource += ['job' => $this->getId()];

        return $this->scheduler->addJob(Sync::class, $resource, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getLog(ObjectIdInterface $id): LogInterface
    {
        return $this->log_factory->getOne($this, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getProcesses(array $query = [], ?int $offset = null, ?int $limit = null, array $sort = []): Generator
    {
        return $this->process_factory->getAll($this, $query, $offset, $limit, $sort);
    }

    /**
     * {@inheritdoc}
     */
    public function getProcess(ObjectIdInterface $id): ProcessInterface
    {
        return $this->process_factory->getOne($this, $id);
    }
}
