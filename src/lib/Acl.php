<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use Generator;
use InvalidArgumentException;
use Micro\Auth\Identity;
use MongoDB\BSON\ObjectId;
use MongoDB\Database;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Tubee\Acl\Exception;
use Tubee\Acl\Role;
use Tubee\Acl\Role\Exception as RoleException;
use Tubee\Acl\Role\RoleInterface;
use Tubee\Acl\Rule;
use Tubee\Acl\Rule\Exception as RuleException;
use Tubee\Acl\Rule\RuleInterface;

class Acl
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
     * Verify request.
     */
    public function isAllowed(ServerRequestInterface $request, Identity $user): bool
    {
        $this->logger->debug('verify access rule for identity ['.$user->getIdentifier().']', [
            'category' => get_class($this),
        ]);

        return true;
        $roles = $this->getRolesByIdentity($user);
        $rules = $this->getRulesByRoles($roles);
        $method = $request->getMethod();
        $attributes = $request->getAttributes();

        foreach ($rules as $rule) {
            $this->logger->debug('verify access rule ['.$rule['_id'].']', [
                'category' => get_class($this),
            ]);

            if (!in_array($method, $rule['verbs']) && !in_array('*', $rule['verbs'])) {
                continue;
            }

            foreach ($rule['selector'] as $selector) {
                if (isset($attributes[$selector]) && (in_array($attributes[$selector], $rule['resoures']) || in_array('*', $rule['resources']))) {
                    return true;
                }
            }
        }

        $this->logger->info('access denied for user ['.$user->getIdentifier().'], no access rule match', [
            'category' => get_class($this),
        ]);

        throw new Exception\NotAllowed('Not allowed to call this resource');
    }

    /**
     * Filter output resources.
     */
    public function filterOutput(ServerRequestInterface $request, Identity $user, Iterable $resources): Generator
    {
        $count = 0;
        foreach ($resources as $resource) {
            ++$count;
            yield $resource;
        }

        if ($resources instanceof Generator) {
            return $resources->getReturn();
        }

        return $count;
    }

    /**
     * Add role.
     */
    public function addRole(array $role): ObjectId
    {
        if (!isset($role['name']) || !is_string($role['name'])) {
            throw new InvalidArgumentException('an access role must have a valid name');
        }

        if (!isset($role['selectors']) || !is_array($role['selectors'])) {
            throw new InvalidArgumentException('an access role must have a field selectors as array');
        }

        foreach ($role as $option => $value) {
            switch ($option) {
                case 'name':
                    if ($this->hasRole($value) === true) {
                        throw new RoleException\NotUnique('an access role name must be unqiue');
                    }

                break;
                case 'selectors':
                break;
                default:
                    throw new InvalidArgumentException('unknown access role option '.$option.' given');
            }
        }

        $result = $this->db->access_roles->insertOne($role);

        return $result->getInsertedId();
    }

    /**
     * Add rule.
     */
    public function addRule(array $rule): ObjectId
    {
        if (!isset($rule['name']) || !is_string($rule['name'])) {
            throw new InvalidArgumentException('an access rule must have a valid name');
        }

        foreach ($rule as $option => $value) {
            switch ($option) {
                case 'name':
                    if ($this->hasRule($value) === true) {
                        throw new RuleException\NotUnique('an access rule name must be unqiue');
                    }

                break;
                case 'verbs':
                case 'roles':
                case 'selectors':
                case 'resources':
                    if (!is_array($value)) {
                        throw new InvalidArgumentException($option.' must be an array of strings');
                    }

                break;
                default:
                    throw new InvalidArgumentException('unknown access rule option '.$option.' given');
            }
        }

        $result = $this->db->access_rules->insertOne($rule);

        return $result->getInsertedId();
    }

    /**
     * Delete role.
     */
    public function deleteRole(string $name): bool
    {
        $result = $this->db->access_roles->remove(['name' => $id]);

        if ($result->nRemoved !== 1) {
            throw new RoleException\NotFound('access role not found');
        }

        return true;
    }

    /**
     * Delete rule.
     */
    public function deleteRule(string $name): bool
    {
        $result = $this->db->access_rules->remove(['name' => $id]);

        if ($result->nRemoved !== 1) {
            throw new RuleException\NotFound('access rule not found');
        }

        return true;
    }

    /**
     * Get roles.
     */
    public function getRoles(?array $query = null, ?int $offset = null, ?int $limit = null): Generator
    {
        $result = $this->db->access_roles->find((array) $query, [
            'offset' => $offset,
            'limit' => $limit,
        ]);

        foreach ($result as $role) {
            yield (string) $role['_id'] => new Role($role);
        }

        return $this->db->access_roles->count((array) $query);
    }

    /**
     * Get rules.
     */
    public function getRules(?array $query = null, ?int $offset = null, ?int $limit = null): Generator
    {
        $result = $this->db->access_rules->find((array) $query, [
            'offset' => $offset,
            'limit' => $limit,
        ]);

        foreach ($result as $rule) {
            yield (string) $rule['_id'] => new Rule($rule);
        }

        return $this->db->access_rules->count((array) $query);
    }

    public function getRole(string $name): RoleInterface
    {
        $result = $this->db->access_roles->findOne(['name' => $name]);
        if ($result === null) {
            throw new RoleException\NotFound('access role not found');
        }

        return new Role($result);
    }

    public function getRule(string $name): RuleInterface
    {
        $result = $this->db->access_rules->findOne(['name' => $name]);
        if ($result === null) {
            throw new RuleException\NotFound('access rule not found');
        }

        return new Rule($result);
    }

    public function hasRole(string $name): bool
    {
        return $this->db->access_roles->count(['name' => $name]) === 1;
    }

    public function hasRule(string $name): bool
    {
        return $this->db->access_rules->count(['name' => $name]) === 1;
    }

    /**
     * Get access rules.
     */
    protected function getRulesByRoles(array $roles)
    {
        return $this->db->access_rules->find([
            'users' => ['$in' => $roles],
        ]);
    }

    /**
     * Get access roles.
     */
    protected function getRolesByIdentity(Identity $user)
    {
        $roles = $this->db->access_roles->find([
            '$or' => [
                ['users' => $user->getIdentifier()],
                ['users' => '*'],
            ],
        ]);

        $roles = array_column(iterator_to_array($roles), '_id');

        if ($roles === null) {
            return [];
        }

        return $roles;
    }
}
