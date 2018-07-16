<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Mandator;

use Generator;
use MongoDB\BSON\ObjectId;
use MongoDB\Database;
use Tubee\Mandator;

class Factory
{
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
     * Has mandator.
     */
    public function has(string $name): bool
    {
        return $this->db->mandators->count(['name' => $name]) > 0;
    }

    /**
     * Get mandators.
     */
    public function getAll(?array $query = null, ?int $offset = null, ?int $limit = null): Generator
    {
        $result = $this->db->mandators->find((array) $query, [
            'offset' => $offset,
            'limit' => $limit,
        ]);

        foreach ($result as $resource) {
            yield (string) $resource['_id'] => self::build($resource);
        }

        return $this->db->mandators->count((array) $query);
    }

    /**
     * Get mandator.
     */
    public function getOne(string $name): MandatorInterface
    {
        $result = $this->db->mandators->findOne(['name' => $name]);

        if ($result === null) {
            throw new Exception\NotFound('mandator '.$name.' is not registered');
        }

        return self::build($result);
    }

    /**
     * Delete by name.
     */
    public function delete(string $name): bool
    {
        if (!$this->has($name)) {
            throw new Exception\NotFound('endpoint '.$name.' does not exists');
        }

        $this->db->mandators->deleteOne(['name' => $name]);

        return true;
    }

    /**
     * Add mandator.
     */
    public function add(array $resource): ObjectId
    {
        Validator::validate($resource);

        if ($this->has($resource['name'])) {
            throw new Exception\NotUnique('mandator '.$name.' does already exists');
        }

        $result = $this->db->mandators->insertOne($resource);

        return $result->getInsertedId();
    }

    /**
     * Build instance.
     */
    public static function build(array $resource): MandatorInterface
    {
        return new Mandator($resource);
    }
}
