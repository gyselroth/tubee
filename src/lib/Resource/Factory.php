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

class Factory
{
    /**
     * Event types.
     */
    /*public const EVENT_ADD = 'ADD';
    public const EVENT_DELETE = 'DELETE';
    public const EVENT_UPDATE = 'UPDATE';*/

    /**
     * Database.
     *
     * @var Database
     */
    protected $db;

    /**
     * Initialize.
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Add resource.
     */
    /*public function addEvent(Collection $collection, string $type, ObjectId $id): ObjectId
    {
        $result = $this->db->events->insertOne([
            'collection' => $collection->getCollectionName(),
            'object' => $id,
            'type' => $type,
        ]);

        return $result->getInsertedId();
    }*/

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
        $this->addEvent($collection, self::EVENT_ADD, $result->getInsertedId());

        return $result->getInsertedId();
    }

    /**
     * Delete resource.
     */
    public function deleteFrom(Collection $collection, array $resource, array $filter): ObjectId
    {
        $result = $collection->deleteOne($filter);
        //$this->addEvent($collection->getName(), self::EVENT_DELETE, $result->getInsertedId());

        return $result->getInsertedId();
    }
}
