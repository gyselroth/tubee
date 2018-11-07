<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Resource;

use Closure;
use Generator;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\ObjectIdInterface;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoDB\Database;
use Psr\Log\LoggerInterface;

class Factory
{
    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Database.
     *
     * @var Database
     */
    protected $db;

    /**
     * Initialize.
     */
    public function __construct(Database $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Add resource.
     */
    public function addTo(Collection $collection, array $resource, bool $simulate = false): ObjectIdInterface
    {
        $ts = new UTCDateTime();
        $resource += [
            'created' => $ts,
            'changed' => $ts,
            'version' => 1,
        ];

        $this->logger->debug('add new resource to ['.$collection->getCollectionName().']', [
            'category' => get_class($this),
            'resource' => $resource,
        ]);

        if ($simulate === true) {
            return new ObjectId();
        }

        $result = $collection->insertOne($resource);
        $id = $result->getInsertedId();

        $this->logger->info('created new resource ['.$id.'] in ['.$collection->getCollectionName().']', [
            'category' => get_class($this),
        ]);

        return $id;
    }

    /**
     * Update resource.
     */
    public function updateIn(Collection $collection, ObjectIdInterface $id, array $resource, bool $simulate = false): bool
    {
        $resource += [
            'changed' => new UTCDateTime(),
        ];

        $this->logger->debug('update resource ['.$id.'] in ['.$collection->getCollectionName().']', [
            'category' => get_class($this),
            'resource' => $resource,
        ]);

        if ($simulate === true) {
            return true;
        }

        $result = $collection->updateOne(['_id' => $id], [
            '$set' => $resource,
            '$inc' => ['version' => 1],
        ]);

        $this->logger->info('updated resource ['.$id.'] in ['.$collection->getCollectionName().']', [
            'category' => get_class($this),
        ]);

        return true;
    }

    /**
     * Delete resource.
     */
    public function deleteFrom(Collection $collection, ObjectIdInterface $id, bool $simulate = false): bool
    {
        $this->logger->info('delete resource ['.$id.'] from ['.$collection->getCollectionName().']', [
            'category' => get_class($this),
        ]);

        if ($simulate === true) {
            return true;
        }

        $result = $collection->deleteOne(['_id' => $id]);

        return true;
    }

    /**
     * Get all.
     */
    public function getAllFrom(Collection $collection, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null, ?Closure $build = null): Generator
    {
        if ($build === null) {
            $build = function ($resource) {
                return $this->build($resource);
            };
        }

        $total = $collection->count($query);

        if ($offset !== null && $total === 0) {
            $offset = null;
        } elseif ($offset < 0 && $total >= $offset * -1) {
            $offset = $total + $offset;
        } elseif ($offset < 0) {
            $offset = 0;
        }

        $result = $collection->find($query, [
            'skip' => $offset,
            'limit' => $limit,
            'sort' => $sort,
        ]);

        foreach ($result as $resource) {
            yield (string) $resource['_id'] => $build->call($this, $resource);
        }

        return $total;
    }

    /**
     * Change stream.
     */
    public function watchFrom(Collection $collection, ?ObjectIdInterface $after = null, bool $existing = true, ?array $query = [], ?Closure $build = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        if ($build === null) {
            $build = function ($resource) {
                return $this->build($resource);
            };
        }

        $pipeline = $query;
        if (!empty($pipeline)) {
            $pipeline = [['$match' => $pipeline]];
        }

        $stream = $collection->watch([], [
            'resumeAfter' => $after,
        ]);

        if ($existing === true) {
            $total = $collection->count($query);

            if ($offset !== null && $total === 0) {
                $offset = null;
            } elseif ($offset < 0 && $total >= $offset * -1) {
                $offset = $total + $offset;
            }

            $result = $collection->find($query, [
                'skip' => $offset,
                'limit' => $limit,
                'sort' => $sort,
            ]);

            foreach ($result as $resource) {
                yield (string) $resource['_id'] => [
                    'insert',
                    $build->call($this, $resource),
                ];
            }
        }

        for ($stream->rewind(); true; $stream->next()) {
            if (!$stream->valid()) {
                continue;
            }

            $event = $stream->current();
            yield (string) $event['fullDocument']['_id'] => [
                $event['operationType'],
                $build->call($this, $event['fullDocument']),
            ];
        }
    }

    /**
     * Build.
     */
    public function initResource(ResourceInterface $resource)
    {
        $this->logger->debug('initialized resource ['.$resource->getId().'] as ['.get_class($resource).']', [
            'category' => get_class($this),
        ]);

        return $resource;
    }
}
