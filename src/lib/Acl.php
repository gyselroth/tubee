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
use Micro\Auth\Identity;
use MongoDB\Database;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Tubee\Acl\Exception;

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
    public function isAllowed(ServerRequestInterface $request, Identity $user)
    {
        $this->logger->debug('verify access rule for identity ['.$user->getIdentifier().']', [
            'category' => get_class($this),
        ]);

        $roles = $this->getRoles($user);
        $rules = $this->getRules($roles);
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
     * Update rule.
     */
    public function updateRule(ObjectId $id, array $rule): bool
    {
    }

    /**
     * Add rule.
     */
    public function addRule(array $rule): ObjectId
    {
    }

    /**
     * Delete rule.
     */
    public function deleteRule(ObjectId $id): bool
    {
    }

    /**
     * Get access rules.
     */
    protected function getRules(array $roles)
    {
        return $this->db->access_rules->find([
            'users' => ['$in' => $roles],
        ]);
    }

    /**
     * Get access roles.
     */
    protected function getRoles(Identity $user)
    {
        $roles = $this->db->access_roles->find([
            'users' => $user->getIdentifier(),
        ]);

        $roles = array_column(iterator_to_array($roles), '_id');

        if ($roles === null) {
            return [];
        }

        return $roles;
    }
}
