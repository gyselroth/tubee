<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Log;

use Generator;
use IteratorIterator;
use MongoDB\BSON\ObjectId;
use MongoDB\Database;
use MongoDB\Operation\Find;
use Psr\Log\LoggerInterface;
use Tubee\Job\JobInterface;
use Tubee\Log;
use Tubee\Resource\Factory as ResourceFactory;

class Factory extends ResourceFactory
{
    /**
     * Collection name.
     */
    public const COLLECTION_NAME = 'logs';

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
    public function build(array $resource): LogInterface
    {
        return $this->initResource(new Log($resource));
    }

    /**
     * Get all.
     */
    public function getAll(JobInterface $job, ?array $query = null, ?int $offset = null, ?int $limit = null): Generator
    {
        $filter = [
            'context.job' => (string) $job->getId(),
        ];

        $result = $this->db->{self::COLLECTION_NAME}->find($filter, [
            'offset' => $offset,
            'limit' => $limit,
        ]);

        foreach ($result as $log) {
            yield (string) $log['_id'] => $this->build($log);
        }

        return $this->db->{self::COLLECTION_NAME}->count($filter);
    }

    /**
     * watch all.
     */
    public function watchAll(JobInterface $job, ?array $query = null, ?int $offset = null, ?int $limit = null): Generator
    {
        $result = $this->db->{self::COLLECTION_NAME}->find([
            'context.job' => (string) $job->getId(),
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
            yield (string) $resource['_id'] => $this->build($resource);
        }
    }

    /**
     * Get job.
     */
    public function getOne(JobInterface $job, ObjectId $log): LogInterface
    {
        $result = $this->db->{self::COLLECTION_NAME}->findOne(['_id' => $log]);

        if ($result === null) {
            throw new Exception\NotFound('log not found');
        }

        return $this->build($result);
    }
}
