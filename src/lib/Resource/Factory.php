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
    public function addTo(Collection $collection, array $resource): ObjectId
    {
        $resource += [
            'created' => new UTCDateTime(),
            'version' => 1,
        ];

        $result = $collection->insertOne($resource);
        $id = $result->getInsertedId();

        $this->logger->info('created new resource ['.$id.'] in ['.$collection->getName().']', [
            'category' => get_class($this),
        ]);

        return $id;
    }

    /**
     * Delete resource.
     */
    public function deleteFrom(Collection $collection, array $resource, array $filter): ObjectId
    {
        $result = $collection->deleteOne($filter);

        $this->logger->info('removed resource ['.$filter.'] from ['.$collection->getName().']', [
            'category' => get_class($this),
        ]);

        return $result->getInsertedId();
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
