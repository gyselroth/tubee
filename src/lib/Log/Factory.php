<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
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
    public function getAll(?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        if (empty($sort)) {
            $sort = ['datetime' => -1];
        }

        return $this->getAllFrom($this->db->{self::COLLECTION_NAME}, $query, $offset, $limit, $sort);
    }

    /**
     * watch all.
     */
    public function watch(?ObjectIdInterface $after = null, bool $existing = true, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        return $this->watchFrom($this->db->{self::COLLECTION_NAME}, $after, $existing, $query, null, $offset, $limit, $sort);
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
