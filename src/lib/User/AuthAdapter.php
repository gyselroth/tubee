<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\User;

use Micro\Auth\Adapter\Basic\AbstractBasic;
use Psr\Log\LoggerInterface;
use Tubee\User\Factory as UserFactory;

class Db extends AbstractBasic
{
    /**
     * User factory.
     *
     * @var UserFactory
     */
    protected $user_factory;

    /**
     * Set options.
     */
    public function __construct(UserFactory $user_factory, LoggerInterface $logger, array $options = [])
    {
        parent::__construct($logger);
        $this->user_factory = $user_factory;
        $this->setOptions($options);
    }

    /**
     * Find identity.
     */
    public function findIdentity(string $username): ?array
    {
        $user = $this->user_factory->findOne($username);

        return [
            'username' => $user->getName(),
            'password' => $user->getPasswordHash(),
        ];
    }

    /**
     * Get attributes.
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Auth.
     */
    public function plainAuth(string $username, string $password): bool
    {
        $result = $this->findIdentity($username);

        if (null === $result) {
            $this->logger->info('found no user named ['.$username.'] in database', [
                'category' => get_class($this),
            ]);

            return false;
        }

        if (!isset($result['password']) || empty($result['password'])) {
            $this->logger->info('found no password for ['.$username.'] in database', [
                'category' => get_class($this),
            ]);

            return false;
        }

        if (!password_verify($password, $result['password'])) {
            $this->logger->info('failed match given password for ['.$username.'] with stored hash in database', [
                'category' => get_class($this),
            ]);

            return false;
        }

        $this->attributes = $result;
        $this->identifier = $username;

        return true;
    }
}
