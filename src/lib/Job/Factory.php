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
use MongoDB\Operation\Find;
use TaskScheduler\Scheduler;
use Traversable;
use Tubee\Job;
use Tubee\Job\Error\ErrorInterface;

class Factory extends Scheduler
{
    /**
     * Get jobs.
     */
    public function getTasks(?array $query = null, ?int $offset = null, ?int $limit = null): Generator
    {
        $result = $this->db->queue->find((array) $query, [
            'offset' => $offset,
            'limit' => $limit,
        ]);

        foreach ($result as $job) {
            yield (string) $job['_id'] => new Job($job);
        }

        return $this->db->queue->count((array) $query);
    }

    /**
     * Get job.
     */
    public function getTask(ObjectId $job): JobInterface
    {
        $result = $this->db->queue->findOne(['_id' => $job]);

        if ($result === null) {
            throw new Exception\NotFound('job not found');
        }

        return new Job($result);
    }

    /**
     * Get jobs errors.
     */
    public function getErrors(ObjectId $job, ?array $query = null, ?int $offset = null, ?int $limit = null): Generator
    {
        $result = $this->db->errors->find([
            'job' => $job,
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
    public function watchErrors(ObjectId $job, ?array $query = null, ?int $offset = null, ?int $limit = null): Traversable
    {
        return $this->db->errors->find([
            'job' => $job,
        ], [
            'offset' => $offset,
            'limit' => $limit,
            'cursorType' => Find::TAILABLE,
            'noCursorTimeout' => true,
        ]);

        /*foreach ($result as $error) {
            yield (string) $error['_id'] => new Error($error);
        }

        return $this->db->erros->count((array) $query);*/
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
