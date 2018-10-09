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
use IteratorIterator;
use MongoDB\BSON\ObjectId;
use MongoDB\Database;
use MongoDB\Operation\Find;
use Psr\Log\LoggerInterface;
use TaskScheduler\Scheduler;
use Tubee\Async\Sync;
use Tubee\Job;
use Tubee\Job\Error\ErrorInterface;
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
    public function __construct(Database $db, Scheduler $scheduler, ProcessFactory $process_factory, LoggerInterface $logger)
    {
        $this->scheduler = $scheduler;
        $this->process_factory = $process_factory;
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
        return $this->initResource(new Job($resource, $this->process_factory));
    }

    /**
     * Get errors.
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
     * Get errors.
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
