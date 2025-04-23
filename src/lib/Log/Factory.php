<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Log;

use Generator;
use MongoDB\BSON\ObjectIdInterface;
use MongoDB\Database;
use Tubee\Job\JobInterface;
use Tubee\Log;
use Tubee\Resource\Factory as ResourceFactory;

class Factory
{
    /**
     * Collection name.
     */
    public const COLLECTION_NAME = 'logs';

    /**
     * Database.
     *
     * @var Database
     */
    protected $db;

    /**
     * Resource factory.
     *
     * @var ResourceFactory
     */
    protected $resource_factory;

    /**
     * Initialize.
     */
    public function __construct(Database $db, ResourceFactory $resource_factory)
    {
        $this->db = $db;
        $this->resource_factory = $resource_factory;
    }

    /**
     * Build instance.
     */
    public function build(array $resource): LogInterface
    {
        return $this->resource_factory->initResource(new Log($resource));
    }

    /**
     * Get all.
     */
    public function getAll(?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        $that = $this;

        return $this->resource_factory->getAllFrom($this->db->{self::COLLECTION_NAME}, $query, $offset, $limit, $sort, function (array $resource) use ($that) {
            return $that->build($resource);
        });
    }

    /**
     * watch all.
     */
    public function watch(?ObjectIdInterface $after = null, bool $existing = true, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        $that = $this;

        return $this->resource_factory->watchFrom($this->db->{self::COLLECTION_NAME}, $after, $existing, $query, function (array $resource) use ($that) {
            return $that->build($resource);
        }, $offset, $limit, $sort);
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
