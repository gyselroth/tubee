<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2021 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\User;

use Micro\Auth\Adapter\Basic\AbstractBasic;
use Micro\Auth\IdentityInterface;
use Psr\Log\LoggerInterface;
use Tubee\User\Factory as UserFactory;

class AuthAdapter extends AbstractBasic
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
     * Get attributes.
     */
    public function getAttributes(IdentityInterface $identity): array
    {
        return [];
    }

    /**
     * Auth.
     */
    public function plainAuth(string $username, string $password): ?array
    {
        $result = $this->user_factory->getOne($username);

        if (null === $result) {
            $this->logger->info('found no user named ['.$username.'] in database', [
                'category' => get_class($this),
            ]);

            return null;
        }

        if (!$result->validatePassword($password)) {
            $this->logger->info('failed match given password for ['.$username.'] with stored hash in database', [
                'category' => get_class($this),
            ]);

            return null;
        }

        return [
            'uid' => $result->getName(),
        ];
    }
}
