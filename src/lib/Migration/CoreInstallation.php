<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2022 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Migration;

use MongoDB\Client;
use MongoDB\Database;
use MongoDB\Driver\Exception\CommandException;
use Tubee\AccessRole\Factory as AccessRoleFactory;
use Tubee\AccessRule\Factory as AccessRuleFactory;
use Tubee\ResourceNamespace\Factory as ResourceNamespaceFactory;
use Tubee\User\Factory as UserFactory;

class CoreInstallation implements DeltaInterface
{
    /**
     * MongoDB Client.
     *
     * @var Client
     */
    protected $client;

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
    public function __construct(Client $client, Database $db, AccessRoleFactory $role_factory, AccessRuleFactory $rule_factory, UserFactory $user_factory, ResourceNamespaceFactory $namespace_factory)
    {
        $this->db = $db;
        $this->client = $client;
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
        try {
            $this->client->selectDatabase('admin')->command(['replSetInitiate' => []]);
        } catch (CommandException $e) {
            if ($e->getCode() !== 23) {
                throw $e;
            }
        }

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
        $this->db->relations->createIndex(['endpoints' => 1]);
        $this->db->relations->createIndex(['namespace' => 1, 'name' => 1], ['unique' => true]);
        $this->db->relations->createIndex(['data.relation.namespace' => 1, 'data.relation.collection' => 1, 'data.relation.object' => 1]);

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

        //remove logs after 14 days
        $this->db->logs->createIndex(['changed' => 1], ['expireAfterSeconds' => 1209600]);
        $this->db->logs->createIndex(['data.level_name' => 1]);
        $this->db->logs->createIndex(['data.context.process' => 1, 'namespace' => 1, 'data.context.parent' => 1]);
        $this->db->logs->createIndex(['namespace' => 1, 'data.context.parent' => 1]);
        $this->db->logs->createIndex(['data.context.job' => 1, 'namespace' => 1]);
        $this->db->logs->createIndex(['collection' => 1, 'namespace' => 1]);
        $this->db->logs->createIndex(['endpoint' => 1, 'collection' => 1, 'namespace' => 1]);
        $this->db->logs->createIndex(['data.context.object' => 1, 'collection' => 1, 'namespace' => 1]);

        //remove taskscheduler jobs after 10 days
        $this->db->taskscheduler->createIndex(['created' => 1], ['expireAfterSeconds' => 864000]);

        return true;
    }
}
