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
    public function deleteOne(ObjectId $id): bool
    {
        try {
            $this->scheduler->cancelJob($id);
        } catch (\Exception $e) {
        }

        $resource = $this->getOne($id);

        return $this->deleteFrom($this->db->{self::COLLECTION_NAME}, $id);
    }

    /**
     * Get job.
     */
    public function getOne(ObjectId $job): JobInterface
    {
        $result = $this->db->{self::COLLECTION_NAME}->findOne(['_id' => $job]);

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
        $options = isset($resource['options']) ? $resource['options'] : [];
        $result = $this->addTo($this->db->{self::COLLECTION_NAME}, $resource);

        $resource += ['job' => $result];
        $this->scheduler->addJob(Sync::class, $resource, $options);

        return $result;
    }

    /**
     * Build instance.
     */
    public function build(array $resource): JobInterface
    {
        return $this->initResource(new Job($resource, $this->process_factory, $this->log_factory));
    }
}
