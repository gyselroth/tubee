<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\AccessRule;

use Generator;
use MongoDB\BSON\ObjectId;
use MongoDB\Database;
use Tubee\AccessRule;
use Tubee\Resource\Factory as ResourceFactory;

class Factory extends ResourceFactory
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
     * Has resource.
     */
    public function has(string $name): bool
    {
        return $this->db->access_rules->count(['name' => $name]) > 0;
    }

    /**
     * Get resources.
     */
    public function getAll(?array $query = null, ?int $offset = null, ?int $limit = null): Generator
    {
        $result = $this->db->access_rules->find((array) $query, [
            'offset' => $offset,
            'limit' => $limit,
        ]);

        foreach ($result as $resource) {
            yield (string) $resource['name'] => self::build($resource);
        }

        return $this->db->access_rules->count((array) $query);
    }

    /**
     * Get resource.
     */
    public function getOne(string $name): AccessRuleInterface
    {
        $result = $this->db->access_rules->findOne(['name' => $name]);

        if ($result === null) {
            throw new Exception\NotFound('access rule '.$name.' is not registered');
        }

        return self::build($result);
    }

    /**
     * Delete by name.
     */
    public function delete(string $name): bool
    {
        if (!$this->has($name)) {
            throw new Exception\NotFound('access rule '.$name.' does not exists');
        }

        $this->db->access_rules->deleteOne(['name' => $name]);

        return true;
    }

    /**
     * Add resource.
     */
    public function add(array $resource): ObjectId
    {
        $resource = Validator::validate($resource);

        if ($this->has($resource['name'])) {
            throw new Exception\NotUnique('access rule '.$resource['name'].' does already exists');
        }

        return parent::addTo($this->db->access_rules, $resource);
    }

    /**
     * Build instance.
     */
    public static function build(array $resource): AccessRuleInterface
    {
        return new AccessRule($resource);
    }
}
