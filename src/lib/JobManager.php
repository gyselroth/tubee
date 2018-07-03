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
use MongoDB\BSON\ObjectId;
use TaskScheduler\Scheduler;
use Tubee\Job\Exception;
use Tubee\Job\Job;
use Tubee\Job\JobInterface;

class JobManager extends Scheduler
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
}
