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
     * Access role factory.
     *
     * @var AccessRoleFactory
     */
    protected $role_factory;

    /**
     * Access rule factory.
     *
     * @var AccessRuleFactory
     */
    protected $rule_factory;

    /**
     * Construct.
     */
    public function __construct(Database $db, AccessRoleFactory $role_factory, AccessRuleFactory $rule_factory)
    {
        $this->db = $db;
        $this->role_factory = $role_factory;
        $this->rule_factory = $rule_factory;
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
        $this->db->jobs->createIndex(['name' => 1], ['unique' => true]);
        $this->db->mandators->createIndex(['name' => 1], ['unique' => true]);
        $this->db->datatypes->createIndex(['name' => 1, 'mandator' => 1], ['unique' => true]);
        $this->db->endpoints->createIndex(['name' => 1, 'endpoint' => 1, 'mandator' => 1], ['unique' => true]);
        $this->db->workflows->createIndex(['name' => 1, 'workflow' => 1, 'endpoint' => 1, 'mandator' => 1], ['unique' => true]);
        $this->db->relations->createIndex(['object_1' => 1, 'object_2' => 1], ['unique' => true]);

        if (!$this->role_factory->has('admin')) {
            $this->role_factory->add([
                'name' => 'admin',
                'selectors' => ['*'],
            ]);
        }

        if (!$this->rule_factory->has('full-access')) {
            $this->rule_factory->add([
                'name' => 'full-access',
                'roles' => ['admin'],
                'verbs' => ['*'],
                'selectors' => ['*'],
                'resources' => ['*'],
            ]);
        }

        if (!in_array('logs', $collections)) {
            $this->db->createCollection(
                'logs',
                [
                'capped' => true,
                'size' => 100000000, ]
            );
        }

        return true;
    }
}
