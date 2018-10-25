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
use MongoDB\BSON\ObjectIdInterface;
use MongoDB\Database;
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

        if (!empty($query)) {
            $filter = ['$and' => [$filter, $query]];
        }

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
    public function watch(JobInterface $job, ?ObjectIdInterface $after = null, bool $existing = true, ?array $query = null): Generator
    {
        $query = [
             'context.job' => (string) $job->getId(),
        ];

        return $this->watchFrom($this->db->{self::COLLECTION_NAME}, $after, $existing, $query);
    }

    /**
     * Get job.
     */
    public function getOne(JobInterface $job, ObjectIdInterface $log): LogInterface
    {
        $result = $this->db->{self::COLLECTION_NAME}->findOne(['_id' => $log]);

        if ($result === null) {
            throw new Exception\NotFound('log not found');
        }

        return $this->build($result);
    }
}
