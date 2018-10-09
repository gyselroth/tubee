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
use Tubee\Job\Error\ErrorInterface;
use Tubee\Log;
use Tubee\Resource\Factory as ResourceFactory;

class Factory extends ResourceFactory
{
    /**
     * Collection name.
     */
    public const COLLECTION_NAME = 'logs';

    /**
     * Job scheduler.
     *
     * @var Scheduler
     */
    protected $scheduler;

    /**
     * Initialize.
     */
    public function __construct(Database $db, LoggerInterface $logger)
    {
        parent::__construct($db, $logger);
    }

    /**
     * Build instance.
     */
    public function build(array $resource): JobInterface
    {
        return $this->initResource(new Log($resource, $this->process_factory));
    }

    /**
     * Get errors.
     */
    public function getAll(ObjectId $job, ?array $query = null, ?int $offset = null, ?int $limit = null): Generator
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
    public function watchAll(ObjectId $job, ?array $query = null, ?int $offset = null, ?int $limit = null): Generator
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
    public function getOne(ObjectId $error): ErrorInterface
    {
        $result = $this->db->errors->findOne(['_id' => $error]);

        if ($result === null) {
            throw new Exception\NotFound('error not found');
        }

        return new Error($result);
    }
}
