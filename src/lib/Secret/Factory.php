<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Secret;

use Generator;
use MongoDB\BSON\ObjectIdInterface;
use MongoDB\Database;
use ParagonIE\Halite\HiddenString;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto as Symmetric;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use Psr\Log\LoggerInterface;
use Tubee\Resource\Factory as ResourceFactory;
use Tubee\Secret;

class Factory extends ResourceFactory
{
    /**
     * Collection name.
     */
    public const COLLECTION_NAME = 'secrets';

    /**
     * Default key.
     */
    private const DEFAULT_KEY = '3140040033da9bd0dedd8babc8b89cda7f2132dd5009cc43c619382863d0c75e172ebf18e713e1987f35d6ea3ace43b561c50d9aefc4441a8c4418f6928a70e4655de5a9660cd323de63b4fd2fb76525470f25311c788c5e366e29bf60c438c4ac0b440e';

    /**
     * Encryption key.
     *
     * @var EncryptionKey
     */
    protected $key;

    /**
     * Initialize.
     */
    public function __construct(Database $db, EncryptionKey $key, LoggerInterface $logger)
    {
        $this->key = $key;
        parent::__construct($db, $logger);
    }

    /**
     * Has secret.
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
     * Get secret.
     */
    public function getOne(string $name): SecretInterface
    {
        $result = $this->db->{self::COLLECTION_NAME}->findOne(['name' => $name]);

        if ($result === null) {
            throw new Exception\NotFound('secret '.$name.' is not registered');
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
     * Add secret.
     */
    public function add(array $resource): ObjectIdInterface
    {
        Validator::validate($resource);

        if ($this->has($resource['name'])) {
            throw new Exception\NotUnique('secret '.$resource['name'].' does already exists');
        }

        if (KeyFactory::export($this->key)->getString() === self::DEFAULT_KEY) {
            throw new Exception\InvalidEncryptionKey('encryption key required to be changed');
        }

        $message = new HiddenString(json_encode($resource['data']));
        $resource['blob'] = Symmetric::encrypt($message, $this->key);
        unset($resource['data']);

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
    public function build(array $resource): SecretInterface
    {
        $decrypted = json_decode(Symmetric::decrypt($resource['data'], $this->key));
        $resource['data'] = $decrypted;
        unset($resource['blob']);

        return $this->initResource(new Secret($resource));
    }
}
