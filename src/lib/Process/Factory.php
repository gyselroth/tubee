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
use IteratorIterator;
use MongoDB\BSON\ObjectId;
use MongoDB\Database;
use MongoDB\Operation\Find;
use Psr\Log\LoggerInterface;
use TaskScheduler\Process;
use TaskScheduler\Scheduler;
use Tubee\Job;
use Tubee\Job\Error\ErrorInterface;
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

        if ($query !== null) {
            $filter = ['$and' => [$filter, $query]];
        }

        $result = $this->scheduler->getJobs($filter, $offset, $limit);

        foreach ($result as $id => $process) {
            yield $id => $this->build($process, $job);
        }

        return $result->getReturn();
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
    public function getOne(JobInterface $job, ObjectId $id): JobInterface
    {
        $result = $this->scheduler->getJob($id);

        return $this->build($result, $job);
    }

    /**
     * Wrap process.
     */
    public function build(Process $resource, JobInterface $job): ProcessInterface
    {
        return $this->initResource(new Process($resource, $job));
    }

    /**
     * Get jobs errors.
     */
    public function getErrors(ObjectId $job, ?array $query = null, ?int $offset = null, ?int $limit = null): Generator
    {
        $result = $this->db->errors->find([
            'context.job' => (string) $job,
        ], [
            'offset' => $offset,
            'limit' => $limit,
        ]);

        foreach ($result as $error) {
            yield (string) $error['_id'] => new Error($error);
        }

        return $this->db->erros->count((array) $query);
    }

    /**
     * Get jobs errors.
     */
    public function watchErrors(ObjectId $job, ?array $query = null, ?int $offset = null, ?int $limit = null): Generator
    {
        $result = $this->db->errors->find([
            'context.job' => (string) $job,
        ], [
            'offset' => $offset,
            'limit' => $limit,
            'cursorType' => Find::TAILABLE,
            'noCursorTimeout' => true,
        ]);

        $iterator = new IteratorIterator($result);
        $iterator->rewind();

        while (true) {
            if (null === $iterator->current()) {
                if ($iterator->getInnerIterator()->isDead()) {
                    return $this->db->erros->count((array) $query);
                }

                $iterator->next();

                continue;
            }

            $resource = $iterator->current();
            $iterator->next();
            yield (string) $resource['_id'] => new Error($resource);
        }
    }

    /**
     * Get job.
     */
    public function getError(ObjectId $error): ErrorInterface
    {
        $result = $this->db->errors->findOne(['_id' => $error]);

        if ($result === null) {
            throw new Exception\NotFound('error not found');
        }

        return new Error($result);
    }
}
