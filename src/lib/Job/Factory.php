<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Job;

use Generator;
use MongoDB\BSON\ObjectId;
use MongoDB\Database;
use Psr\Log\LoggerInterface;
use TaskScheduler\Scheduler;
use Tubee\Async\Sync;
use Tubee\Job;
use Tubee\Log\Factory as LogFactory;
use Tubee\Process\Factory as ProcessFactory;
use Tubee\Resource\Factory as ResourceFactory;

class Factory extends ResourceFactory
{
    /**
     * Collection name.
     */
    public const COLLECTION_NAME = 'jobs';

    /**
     * Job scheduler.
     *
     * @var Scheduler
     */
    protected $scheduler;

    /**
     * Initialize.
     */
    public function __construct(Database $db, Scheduler $scheduler, ProcessFactory $process_factory, LogFactory $log_factory, LoggerInterface $logger)
    {
        $this->scheduler = $scheduler;
        $this->process_factory = $process_factory;
        $this->log_factory = $log_factory;
        parent::__construct($db, $logger);
    }

    /**
     * Get all.
     */
    public function getAll(?array $query = null, ?int $offset = null, ?int $limit = null): Generator
    {
        $result = $this->db->{self::COLLECTION_NAME}->find((array) $query, [
            'offset' => $offset,
            'limit' => $limit,
        ]);

        foreach ($result as $job) {
            yield (string) $job['_id'] => $this->build($job);
        }

        return $this->db->{self::COLLECTION_NAME}->count((array) $query);
    }

    /**
     * Delete by name.
     */
    public function deleteOne(string $name): bool
    {
        $job = $this->getOne($name);

        foreach ($this->scheduler->getJobs(['data.job' => $name]) as $task) {
            try {
                $this->scheduler->cancelJob($task['_id']);
            } catch (\Exception $e) {
            }
        }

        return $this->deleteFrom($this->db->{self::COLLECTION_NAME}, $job->getId());
    }

    /**
     * Get job.
     */
    public function getOne(string $name): JobInterface
    {
        $result = $this->db->{self::COLLECTION_NAME}->findOne(['name' => $name]);

        if ($result === null) {
            throw new Exception\NotFound('job not found');
        }

        return $this->build($result);
    }

    /**
     * Create resource.
     */
    public function create(array $resource): ObjectId
    {
        $resource = Validator::validate($resource);

        if ($this->has($resource['name'])) {
            throw new Exception\NotUnique('job '.$resource['name'].' does already exists');
        }

        $result = $this->addTo($this->db->{self::COLLECTION_NAME}, $resource);

        $resource += ['job' => $result];
        $this->scheduler->addJob(Sync::class, $resource, $resource['options']);

        return $result;
    }

    /**
     * Has.
     */
    public function has(string $name): bool
    {
        return $this->db->{self::COLLECTION_NAME}->count(['name' => $name]) > 0;
    }

    /**
     * Update.
     */
    public function update(JobInterface $resource, array $data): bool
    {
        $data = Validator::validate($data);

        return $this->updateIn($this->db->{self::COLLECTION_NAME}, $resource->getId(), $data);
    }

    /**
     * Build instance.
     */
    public function build(array $resource): JobInterface
    {
        return $this->initResource(new Job($resource, $this->process_factory, $this->log_factory));
    }
}
