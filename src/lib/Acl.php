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
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Tubee\AccessRole\Factory as AccessRoleFactory;
use Tubee\AccessRule\Factory as AccessRuleFactory;
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
     * Initialize.
     */
    public function __construct(AccessRoleFactory $role, AccessRuleFactory $rule, LoggerInterface $logger)
    {
        $this->role = $role;
        $this->rule = $rule;
        $this->logger = $logger;
    }

    /**
     * Verify request.
     */
    public function isAllowed(ServerRequestInterface $request, Identity $user): bool
    {
        $this->logger->debug('verify access for identity ['.$user->getIdentifier().']', [
            'category' => get_class($this),
        ]);

        $roles = $this->role->getAll([
            '$or' => [
                ['selectors' => $user->getIdentifier()],
                ['selectors' => '*'],
            ],
        ]);

        $names = [];
        foreach ($roles as $role) {
            $names[] = $role->getName();
        }

        if ($names === []) {
            $this->logger->info('no matching access roles for ['.$user->getIdentifier().']', [
                'category' => get_class($this),
            ]);

            throw new Exception\NotAllowed('Not allowed to call this resource');
        }

        $rules = $this->rule->getAll([
            'roles' => ['$in' => $names],
        ]);

        $method = $request->getMethod();
        $attributes = $request->getAttributes();

        foreach ($rules as $rule) {
            $rule = $rule->toArray();

            $this->logger->debug('verify access rule ['.$rule['name'].']', [
                'category' => get_class($this),
            ]);

            if (empty(array_intersect($names, $rule['roles'])) && !in_array('*', $rule['roles'])) {
                continue;
            }

            if (!in_array($method, $rule['verbs']) && !in_array('*', $rule['verbs'])) {
                continue;
            }

            foreach ($rule['selectors'] as $selector) {
                if ($selector === '*') {
                    return true;
                }

                if (isset($attributes[$selector]) && (in_array($attributes[$selector], $rule['resources']) || in_array('*', $rule['resources']))) {
                    return true;
                }
            }
        }

        $this->logger->info('access denied for user ['.$user->getIdentifier().'], no access rule match', [
            'category' => get_class($this),
            'roles' => $names,
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
}
