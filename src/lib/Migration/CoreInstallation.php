<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Migration;

use MongoDB\Database;
use Tubee\AccessRole\Factory as AccessRoleFactory;
use Tubee\AccessRule\Factory as AccessRuleFactory;
use Tubee\ResourceNamespace\Factory as ResourceNamespaceFactory;
use Tubee\User\Factory as UserFactory;

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
     * User factory.
     *
     * @var UserFactory
     */
    protected $user_factory;

    /**
     * Resource namespace factory.
     *
     * @var ResourceNamespaceFactory
     */
    protected $namespace_factory;

    /**
     * Construct.
     */
    public function __construct(Database $db, AccessRoleFactory $role_factory, AccessRuleFactory $rule_factory, UserFactory $user_factory, ResourceNamespaceFactory $namespace_factory)
    {
        $this->db = $db;
        $this->role_factory = $role_factory;
        $this->rule_factory = $rule_factory;
        $this->user_factory = $user_factory;
        $this->namespace_factory = $namespace_factory;
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
        $this->db->secrets->createIndex(['name' => 1, 'namespace' => 1], ['unique' => true]);
        $this->db->users->createIndex(['name' => 1], ['unique' => true]);
        $this->db->jobs->createIndex(['name' => 1, 'namespace' => 1], ['unique' => true]);
        $this->db->namespaces->createIndex(['name' => 1], ['unique' => true]);
        $this->db->collections->createIndex(['name' => 1, 'namespace' => 1], ['unique' => true]);
        $this->db->endpoints->createIndex(['name' => 1, 'collection' => 1, 'namespace' => 1], ['unique' => true]);
        $this->db->workflows->createIndex(['name' => 1, 'collection' => 1, 'endpoint' => 1, 'namespace' => 1], ['unique' => true]);
        $this->db->relations->createIndex(['data.relation' => 1, 'name' => 1]);
        $this->db->relations->createIndex(['namespace' => 1, 'name' => 1], ['unique' => true]);

        if (!$this->namespace_factory->has('default')) {
            $this->namespace_factory->add([
                'name' => 'default',
            ]);
        }

        if (!$this->user_factory->has('admin')) {
            $this->user_factory->add([
                'name' => 'admin',
                'data' => [
                    'password' => 'admin',
                ],
            ]);
        }

        if (!$this->role_factory->has('admin')) {
            $this->role_factory->add([
                'name' => 'admin',
                'data' => [
                    'selectors' => ['*'],
                ],
            ]);
        }

        if (!$this->rule_factory->has('full-access')) {
            $this->rule_factory->add([
                'name' => 'full-access',
                'data' => [
                    'roles' => ['admin'],
                    'verbs' => ['*'],
                    'selectors' => ['*'],
                    'resources' => ['*'],
                ],
            ]);
        }

        if (!in_array('logs', $collections)) {
            $this->db->createCollection(
                'logs',
                [
                'capped' => true,
                'size' => 10000000, ]
            );
        }

        /*$this->db->logs->createIndex(['context.process' => 1, 'context.parent' => 1]);
        $this->db->logs->createIndex(['context.process' => 1]);
        $this->db->logs->createIndex(['context.parent' => 1]);*/

        $this->db->logs->createIndex(['context.process' => 1, 'context.namespace' => 1]);
        $this->db->logs->createIndex(['context.job' => 1, 'context.namespace' => 1]);
        $this->db->logs->createIndex(['context.collection' => 1, 'context.namespace' => 1]);
        $this->db->logs->createIndex(['context.endpoint' => 1, 'context.collection' => 1, 'context.namespace' => 1]);
        $this->db->logs->createIndex(['context.object' => 1, 'context.collection' => 1, 'context.namespace' => 1]);

        return true;
    }
}
