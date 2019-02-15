<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Process;

use Generator;
use MongoDB\BSON\ObjectIdInterface;
use MongoDB\Database;
use TaskScheduler\Process;
use TaskScheduler\Scheduler;
use Tubee\Async\Sync;
use Tubee\Job;
use Tubee\Log\Factory as LogFactory;
use Tubee\Process as ProcessWrapper;
use Tubee\Resource\Factory as ResourceFactory;
use Tubee\ResourceNamespace\ResourceNamespaceInterface;

class Factory
{
    /**
     * Database.
     *
     * @var Database
     */
    protected $db;

    /**
     * Resource factory.
     *
     * @var ResourceFactory
     */
    protected $resource_factory;

    /**
     * Job scheduler.
     *
     * @var Scheduler
     */
    protected $scheduler;

    /**
     * Log factory.
     *
     * @var LogFactory
     */
    protected $log_factory;

    /**
     * Initialize.
     */
    public function __construct(Database $db, ResourceFactory $resource_factory, Scheduler $scheduler, LogFactory $log_factory)
    {
        $this->db = $db;
        $this->resource_factory = $resource_factory;
        $this->scheduler = $scheduler;
        $this->log_factory = $log_factory;
    }

    /**
     * Get jobs.
     */
    public function getAll(ResourceNamespaceInterface $namespace, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        $filter = [
            'status' => ['$exists' => true],
            'data.namespace' => $namespace->getName(),
        ];

        if (!empty($query)) {
            $filter = [
                '$and' => [$filter, $query],
            ];
        }

        $that = $this;

        return $this->resource_factory->getAllFrom($this->db->{$this->scheduler->getJobQueue()}, $filter, $offset, $limit, $sort, function (array $resource) use ($namespace, $that) {
            return $that->build($resource, $namespace);
        });
    }

    /**
     * Create resource.
     */
    public function create(ResourceNamespaceInterface $namespace, array $resource): ObjectIdInterface
    {
        $resource['kind'] = 'Process';
        $resource = $this->resource_factory->validate($resource);
        $resource['data']['namespace'] = $namespace->getName();

        $process = $this->scheduler->addJob(Sync::class, $resource['data']);

        return $process->getId();
    }

    /**
     * Delete by name.
     */
    public function deleteOne(ResourceNamespaceInterface $namespace, ObjectIdInterface $id): bool
    {
        $cursor = $this->db->{$this->scheduler->getJobQueue()}->find([
            '$or' => [
                ['_id' => $id],
                ['data.parent' => $id],
            ],
        ]);

        foreach ($cursor as $process) {
            $this->scheduler->cancelJob($process['_id']);
            if (isset($process['data']['parent']) && $process['options']['interval'] !== 0) {
                $this->scheduler->addJob(Sync::class, $process['data'], $process['options']);
            }
        }

        return true;
    }

    /**
     * Get job.
     */
    public function getOne(ResourceNamespaceInterface $namespace, ObjectIdInterface $id): ProcessInterface
    {
        $result = $this->scheduler->getJob($id);

        return $this->build($result->toArray(), $namespace);
    }

    /**
     * Change stream.
     */
    public function watch(ResourceNamespaceInterface $namespace, ?ObjectIdInterface $after = null, bool $existing = true, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        $that = $this;

        return $this->resource_factory->watchFrom($this->db->{$this->scheduler->getJobQueue()}, $after, $existing, $query, function (array $resource) use ($namespace, $that) {
            return $that->build($resource, $namespace);
        }, $offset, $limit, $sort);
    }

    /**
     * Wrap process.
     */
    public function build(array $process, ResourceNamespaceInterface $namespace): ProcessInterface
    {
        return $this->resource_factory->initResource(new ProcessWrapper($process, $namespace, $this->log_factory));
    }
}
