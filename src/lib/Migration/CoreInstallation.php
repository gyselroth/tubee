<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Migration;

use MongoDB\Database;
use Tubee\AccessRole\Factory as AccessRoleFactory;
use Tubee\AccessRule\Factory as AccessRuleFactory;

class CoreInstallation implements DeltaInterface
{
    /**
     * Database.
     *
     * @var Database
     */
    protected $db;

    /**
     * Construct.
     */
    public function __construct(Database $db, AccessRoleFactory $role, AccessRuleFactory $rule)
    {
        $this->db = $db;
        $this->role = $role;
        $this->rule = $rule;
    }

    /**
     * Initialize defaults.
     */
    public function start(): bool
    {
        $collections = [];
        foreach ($this->db->listCollections() as $collection) {
            $collections[] = $collection->getName();
        }

        $this->db->access_roles->createIndex(['name' => 1], ['unique' => true]);
        $this->db->access_rules->createIndex(['name' => 1], ['unique' => true]);
        $this->db->mandators->createIndex(['name' => 1], ['unique' => true]);
        $this->db->datatypes->createIndex(['name' => 1, 'mandator' => 1], ['unique' => true]);
        $this->db->endpoints->createIndex(['name' => 1, 'endpoint' => 1, 'mandator' => 1], ['unique' => true]);
        $this->db->workflows->createIndex(['name' => 1, 'workflow' => 1, 'endpoint' => 1, 'mandator' => 1], ['unique' => true]);

        if (!$this->role->has('admin')) {
            $this->role->add([
                'name' => 'admin',
                'selectors' => ['*'],
            ]);
        }

        if (!$this->rule->has('full-access')) {
            $this->rule->add([
                'name' => 'full-access',
                'roles' => ['admin'],
                'verbs' => ['*'],
                'selectors' => ['*'],
                'resources' => ['*'],
            ]);
        }

        if (!in_array('errors', $collections)) {
            $this->db->createCollection(
                'errors',
                [
                'capped' => true,
                'size' => 100000, ]
            );
        }

        if (!in_array('events', $collections)) {
            $this->db->createCollection(
                'events',
                [
                'capped' => true,
                'size' => 100000, ]
            );
        }

        return true;
    }
}
