<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2021 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Job;

use Generator;
use MongoDB\BSON\ObjectIdInterface;
use MongoDB\Database;
use Psr\Log\LoggerInterface;
use TaskScheduler\Scheduler;
use Tubee\Async\Sync;
use Tubee\Job;
use Tubee\Log\Factory as LogFactory;
use Tubee\Process\Factory as ProcessFactory;
use Tubee\Resource\Factory as ResourceFactory;
use Tubee\ResourceNamespace\ResourceNamespaceInterface;

class Factory
{
    /**
     * Collection name.
     */
    public const COLLECTION_NAME = 'jobs';

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
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Initialize.
     */
    public function __construct(Database $db, ResourceFactory $resource_factory, Scheduler $scheduler, ProcessFactory $process_factory, LogFactory $log_factory, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->resource_factory = $resource_factory;
        $this->scheduler = $scheduler;
        $this->process_factory = $process_factory;
        $this->log_factory = $log_factory;
        $this->logger = $logger;
    }

    /**
     * Get all.
     */
    public function getAll(ResourceNamespaceInterface $namespace, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        $filter = $this->prepareQuery($namespace, $query);
        $that = $this;

        return $this->resource_factory->getAllFrom($this->db->{self::COLLECTION_NAME}, $filter, $offset, $limit, $sort, function (array $resource) use ($namespace, $that) {
            return $that->build($resource, $namespace);
        });
    }

    /**
     * Delete by name.
     */
    public function deleteOne(ResourceNamespaceInterface $namespace, string $name): bool
    {
        $job = $this->getOne($namespace, $name);

        $tasks = $this->scheduler->getJobs([
            'data.namespace' => $namespace->getName(),
            'data.job' => $name,
        ]);

        foreach ($tasks as $task) {
            try {
                $this->scheduler->cancelJob($task->getId());
            } catch (\Exception $e) {
                $this->logger->error('failed to cancel job [{job}]', [
                    'category' => get_class($this),
                    'exception' => $e,
                    'job' => $task->getId(),
                ]);
            }
        }

        return $this->resource_factory->deleteFrom($this->db->{self::COLLECTION_NAME}, $job->getId());
    }

    /**
     * Get job.
     */
    public function getOne(ResourceNamespaceInterface $namespace, string $name): JobInterface
    {
        $result = $this->db->{self::COLLECTION_NAME}->findOne([
            'namespace' => $namespace->getName(),
            'name' => $name,
        ], [
            'projection' => ['history' => 0],
        ]);

        if ($result === null) {
            throw new Exception\NotFound('job not found');
        }

        return $this->build($result, $namespace);
    }

    /**
     * Create resource.
     */
    public function create(ResourceNamespaceInterface $namespace, array $resource): ObjectIdInterface
    {
        $resource['kind'] = 'Job';
        $resource = $this->resource_factory->validate($resource);
        $resource = Validator::validate($resource);

        if ($this->has($namespace, $resource['name'])) {
            throw new Exception\NotUnique('job '.$resource['name'].' does already exists');
        }

        $resource['namespace'] = $namespace->getName();
        $result = $this->resource_factory->addTo($this->db->{self::COLLECTION_NAME}, $resource);

        $resource['data'] += [
            'namespace' => $namespace->getName(),
            'job' => $resource['name'],
            'error_count' => 0,
        ];

        if ($resource['data']['active'] === true) {
            $this->scheduler->addJob(Sync::class, $resource['data'], $resource['data']['options']);
        }

        return $result;
    }

    /**
     * Has.
     */
    public function has(ResourceNamespaceInterface $namespace, string $name): bool
    {
        return $this->db->{self::COLLECTION_NAME}->count([
            'namespace' => $namespace->getName(),
            'name' => $name,
        ]) > 0;
    }

    /**
     * Update.
     */
    public function update(JobInterface $resource, array $data): bool
    {
        $data['name'] = $resource->getName();
        $data['kind'] = $resource->getKind();

        $data = $this->resource_factory->validate($data);
        $data = Validator::validate($data);

        $task = $data['data'];
        $task += [
            'job' => $resource->getName(),
            'namespace' => $resource->getResourceNamespace()->getName(),
            'error_count' => 0,
        ];

        if ($data['data']['active'] === true) {
            $this->scheduler->addJobOnce(Sync::class, $task, $task['options']);
        } else {
            $procs = $this->scheduler->getJobs([
                'data.namespace' => $resource->getResourceNamespace()->getName(),
                'data.job' => $resource->getName(),
                'status' => ['$lt' => 3],
            ]);

            foreach ($procs as $proc) {
                try {
                    $this->scheduler->cancelJob($proc->getId());
                } catch (\Exception $e) {
                    $this->logger->error('failed to cancel job [{job}]', [
                        'category' => get_class($this),
                        'exception' => $e,
                        'job' => $proc->getId(),
                    ]);
                }
            }
        }

        return $this->resource_factory->updateIn($this->db->{self::COLLECTION_NAME}, $resource, $data);
    }

    /**
     * Change stream.
     */
    public function watch(ResourceNamespaceInterface $namespace, ?ObjectIdInterface $after = null, bool $existing = true, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        $filter = $this->prepareQuery($namespace, $query);
        $that = $this;

        return $this->resource_factory->watchFrom($this->db->{self::COLLECTION_NAME}, $after, $existing, $filter, function (array $resource) use ($namespace, $that) {
            return $that->build($resource, $namespace);
        }, $offset, $limit, $sort);
    }

    /**
     * Build instance.
     */
    public function build(array $resource, ResourceNamespaceInterface $namespace): JobInterface
    {
        return $this->resource_factory->initResource(new Job($resource, $namespace, $this->scheduler, $this->process_factory, $this->log_factory));
    }

    /**
     * Prepare query.
     */
    protected function prepareQuery(ResourceNamespaceInterface $namespace, ?array $query = null): array
    {
        $filter = [
            'namespace' => $namespace->getName(),
        ];

        if (!empty($query)) {
            $filter = [
                '$and' => [$filter, $query],
            ];
        }

        return $filter;
    }
}
