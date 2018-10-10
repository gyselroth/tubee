<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Resource;

use MongoDB\BSON\ObjectId;
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
    public function addTo(Collection $collection, array $resource, bool $simulate = false): ObjectId
    {
        $resource += [
            'created' => new UTCDateTime(),
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
    public function updateIn(Collection $collection, ObjectId $id, array $resource, bool $simulate = false): bool
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

        $this->logger->info('created new resource ['.$id.'] in ['.$collection->getCollectionName().']', [
            'category' => get_class($this),
        ]);

        return true;
    }

    /**
     * Delete resource.
     */
    public function deleteFrom(Collection $collection, ObjectId $id, bool $simulate = false): bool
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
