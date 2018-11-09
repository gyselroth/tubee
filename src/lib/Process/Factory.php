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
use Tubee\Async\Sync;
use Tubee\Job;
use Tubee\Log\Factory as LogFactory;
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
     * Log factory.
     *
     * @var LogFactory
     */
    protected $log_factory;

    /**
     * Initialize.
     */
    public function __construct(Database $db, Scheduler $scheduler, LogFactory $log_factory, LoggerInterface $logger)
    {
        $this->scheduler = $scheduler;
        $this->log_factory = $log_factory;
        parent::__construct($db, $logger);
    }

    /**
     * Get jobs.
     */
    public function getAll(?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        //TaskScheduler dummy entry fix
        $filter = ['status' => ['$exists' => true]];
        if (!empty($query)) {
            $filter = ['$and' => [$filter, $query]];
        }

        return $this->getAllFrom($this->db->{$this->scheduler->getJobQueue()}, $filter, $offset, $limit, $sort);
    }

    /**
     * Create resource.
     */
    public function create(array $resource): ObjectIdInterface
    {
        $resource = Validator::validate($resource);
        $process = $this->scheduler->addJob(Sync::class, $resource['data']);

        return $process->getId();
    }

    /**
     * Delete by name.
     */
    public function deleteOne(ObjectIdInterface $id): bool
    {
        $this->scheduler->cancelJob($id);

        return true;
    }

    /**
     * Get job.
     */
    public function getOne(ObjectIdInterface $id): ProcessInterface
    {
        $result = $this->scheduler->getJob($id);

        return $this->build($result->toArray());
    }

    /**
     * Change stream.
     */
    public function watch(?ObjectIdInterface $after = null, bool $existing = true, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        return $this->watchFrom($this->db->{$this->scheduler->getJobQueue()}, $after, $existing, $query, null, $offset, $limit, $sort);
    }

    /**
     * Wrap process.
     */
    public function build(array $process): ProcessInterface
    {
        return $this->initResource(new ProcessWrapper($process, $this->log_factory));
    }
}
