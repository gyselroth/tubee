<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\User;

use Generator;
use InvalidArgumentException;
use MongoDB\BSON\ObjectIdInterface;
use MongoDB\Database;
use Psr\Log\LoggerInterface;
use Tubee\Resource\Factory as ResourceFactory;
use Tubee\User;

class Factory extends ResourceFactory
{
    /**
     * Collection name.
     */
    public const COLLECTION_NAME = 'users';

    /**
     * Password policy.
     *
     * @var string
     */
    protected $password_policy = '/.*/';

    /**
     * Password hash.
     *
     * @var int
     */
    protected $password_hash = PASSWORD_DEFAULT;

    /**
     * Initialize.
     */
    public function __construct(Database $db, LoggerInterface $logger, array $options = [])
    {
        $this->setOptions($options);
        parent::__construct($db, $logger);
    }

    /**
     * Set options.
     */
    public function setOptions(array $config = []): self
    {
        foreach ($config as $name => $value) {
            switch ($name) {
                case 'password_policy':
                    $this->{$name} = (string) $value;

                break;
                case 'password_hash':
                    $this->{$name} = (int) $value;

                break;
                default:
                    throw new InvalidArgumentException('invalid option '.$name.' given');
            }
        }

        return $this;
    }

    /**
     * Has user.
     */
    public function has(string $name): bool
    {
        return $this->db->{self::COLLECTION_NAME}->count(['name' => $name]) > 0;
    }

    /**
     * Get all.
     */
    public function getAll(?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        return $this->getAllFrom($this->db->{self::COLLECTION_NAME}, $query, $offset, $limit, $sort);
    }

    /**
     * Get user.
     */
    public function getOne(string $name): UserInterface
    {
        $result = $this->db->{self::COLLECTION_NAME}->findOne([
            'name' => $name,
        ], [
            'projection' => ['history' => 0],
        ]);

        if ($result === null) {
            throw new Exception\NotFound('user '.$name.' is not registered');
        }

        return $this->build($result);
    }

    /**
     * Delete by name.
     */
    public function deleteOne(string $name): bool
    {
        $resource = $this->getOne($name);

        return $this->deleteFrom($this->db->{self::COLLECTION_NAME}, $resource->getId());
    }

    /**
     * Update.
     */
    public function update(UserInterface $resource, array $data): bool
    {
        $data['name'] = $resource->getName();
        $data['kind'] = $resource->getKind();
        $data = $this->validate($data);
        $data = Validator::validatePolicy($data, $this->password_policy);

        return $this->updateIn($this->db->{self::COLLECTION_NAME}, $resource, $data);
    }

    /**
     * Add user.
     */
    public function add(array $resource): ObjectIdInterface
    {
        $resource['kind'] = 'User';
        $resource = $this->validate($resource);
        Validator::validatePolicy($resource, $this->password_policy);

        if ($this->has($resource['name'])) {
            throw new Exception\NotUnique('user '.$resource['name'].' does already exists');
        }

        $resource['hash'] = password_hash($resource['data']['password'], $this->password_hash);
        unset($resource['data']['password']);

        return $this->addTo($this->db->{self::COLLECTION_NAME}, $resource);
    }

    /**
     * Change stream.
     */
    public function watch(?ObjectIdInterface $after = null, bool $existing = true, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        return $this->watchFrom($this->db->{self::COLLECTION_NAME}, $after, $existing, $query, null, $offset, $limit, $sort);
    }

    /**
     * Build instance.
     */
    public function build(array $resource): UserInterface
    {
        return $this->initResource(new User($resource));
    }
}
