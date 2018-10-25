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
use MongoDB\BSON\ObjectId;
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
    public function getAll(JobInterface $job, ?array $query = null, ?int $offset = null, ?int $limit = null): Generator
    {
        $filter = [
            'data.job' => $job->getId(),
        ];

        if (!empty($query)) {
            $filter = ['$and' => [$filter, $query]];
        }

        $result = $this->scheduler->getJobs($filter, $offset, $limit);

        foreach ($result as $id => $process) {
            yield $id => $this->build($process, $job);
        }

        return (int) $result->getReturn();
    }

    /**
     * Delete by name.
     */
    public function deleteOne(JobInterface $job, ObjectId $id): bool
    {
        $this->scheduler->cancelJob($id);

        return true;
    }

    /**
     * Get job.
     */
    public function getOne(JobInterface $job, ObjectId $id): ProcessInterface
    {
        $result = $this->scheduler->getJob($id);

        return $this->build($result, $job);
    }

    /**
     * Change stream.
     */
    public function watch(JobInterface $job, ?ObjectId $after = null, bool $existing = true): Generator
    {
        return $this->watchFrom($this->db->{self::COLLECTION_NAME}, $after, $existing, [], function (array $resource) use ($job) {
            return $this->build($resource->toArray(), $resource, $job);
        });
    }

    /**
     * Wrap process.
     */
    public function build(Process $process, JobInterface $job): ProcessInterface
    {
        return $this->initResource(new ProcessWrapper($process->toArray(), $process, $job));
    }
}
