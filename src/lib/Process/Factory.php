<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Process;

use Generator;
use MongoDB\BSON\ObjectIdInterface;
use MongoDB\Database;
use Psr\Log\LoggerInterface;
use TaskScheduler\Process;
use TaskScheduler\Scheduler;
use Tubee\Job;
use Tubee\Job\JobInterface;
use Tubee\Process as ProcessWrapper;
use Tubee\Resource\Factory as ResourceFactory;

class Factory extends ResourceFactory
{
    /**
     * Job scheduler.
     *
     * @var Scheduler
     */
    protected $scheduler;

    /**
     * Initialize.
     */
    public function __construct(Database $db, Scheduler $scheduler, LoggerInterface $logger)
    {
        $this->scheduler = $scheduler;
        parent::__construct($db, $logger);
    }

    /**
     * Get jobs.
     */
    public function getAll(JobInterface $job, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort=null): Generator
    {
        $filter = [
            'data.job' => $job->getId(),
        ];

        if (!empty($query)) {
            $filter = ['$and' => [$filter, $query]];
        }

        return $this->getAllFrom($this->db->{$this->scheduler->getCollection()}, $filter, $offset, $limit, $sort, function(array $resource) use($job) {
            return $this->build($resource, $job)
        });
    }

    /**
     * Delete by name.
     */
    public function deleteOne(JobInterface $job, ObjectIdInterface $id): bool
    {
        $this->scheduler->cancelJob($id);

        return true;
    }

    /**
     * Get job.
     */
    public function getOne(JobInterface $job, ObjectIdInterface $id): ProcessInterface
    {
        $result = $this->scheduler->getJob($id);

        return $this->build($result->toArray(), $job);
    }

    /**
     * Change stream.
     */
    public function watch(JobInterface $job, ?ObjectIdInterface $after = null, bool $existing = true, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort=null): Generator
    {
        return $this->watchFrom($this->db->{$this->scheduler->getCollection()}, $after, $existing, $query, function (array $resource) use ($job) {
            return $this->build($resource, $job);
        }, $offset, $limit, $sort);
    }

    /**
     * Wrap process.
     */
    public function build(array $process, JobInterface $job): ProcessInterface
    {
        return $this->initResource(new ProcessWrapper($process, $job));
    }
}
