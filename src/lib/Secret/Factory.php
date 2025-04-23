<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Secret;

use Generator;
use MongoDB\BSON\ObjectIdInterface;
use MongoDB\Database;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto as Symmetric;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use ParagonIE\HiddenString\HiddenString;
use Psr\Log\LoggerInterface;
use Tubee\Helper;
use Tubee\Resource\Factory as ResourceFactory;
use Tubee\Resource\ResourceInterface;
use Tubee\ResourceNamespace\ResourceNamespaceInterface;
use Tubee\Secret;

class Factory
{
    /**
     * Collection name.
     */
    public const COLLECTION_NAME = 'secrets';

    /**
     * Default key.
     */
    private const DEFAULT_KEY = '314004004b3cef33ba8ea540b424736408364317d9ebfbc9293b8478a8d2478e23dba1ba30ded48ab0dd059cfe3dce2daf00d10eb40af1c0bf429553a2d64802272a514cfde95ac31956baa3929ee01c7338c95805c3a619e254f7aa2966e6a7cdad4783';

    /**
     * Database.
     *
     * @var Database
     */
    protected $db;

    /**
     * Resource factory.
     *
     * @var ResourceFactory
     */
    protected $resource_factory;

    /**
     * Encryption key.
     *
     * @var EncryptionKey
     */
    protected $key;

    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Initialize.
     */
    public function __construct(Database $db, ResourceFactory $resource_factory, EncryptionKey $key, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->resource_factory = $resource_factory;
        $this->key = $key;
        $this->logger = $logger;
    }

    /**
     * Has secret.
     */
    public function has(ResourceNamespaceInterface $namespace, string $name): bool
    {
        return $this->db->{self::COLLECTION_NAME}->count([
            'namespace' => $namespace->getName(),
            'name' => $name,
        ]) > 0;
    }

    /**
     * Get all.
     */
    public function getAll(ResourceNamespaceInterface $namespace, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        $filter = $this->prepareQuery($namespace, $query);
        $that = $this;

        return $this->resource_factory->getAllFrom($this->db->{self::COLLECTION_NAME}, $filter, $offset, $limit, $sort, function (array $resource) use ($namespace, $that) {
            return $that->build($resource, $namespace);
        });
    }

    /**
     * Get secret.
     */
    public function getOne(ResourceNamespaceInterface $namespace, string $name): SecretInterface
    {
        $result = $this->db->{self::COLLECTION_NAME}->findOne([
            'namespace' => $namespace->getName(),
            'name' => $name,
        ], [
            'projection' => ['history' => 0],
        ]);

        if ($result === null) {
            throw new Exception\NotFound('secret '.$name.' is not registered');
        }

        return $this->build($result, $namespace);
    }

    /**
     * Resolve resource secrets.
     */
    public function resolve(ResourceNamespaceInterface $namespace, array $resource): array
    {
        if (isset($resource['secrets'])) {
            $this->logger->info('found secrets to resolve for resource ['.$resource['name'].']', [
                'category' => get_class($this),
            ]);

            foreach ($resource['secrets'] as $secret) {
                try {
                    $blob = $this->getOne($namespace, $secret['secret'])->getData();
                    $data = base64_decode(Helper::getArrayValue($blob, $secret['key']));
                    $resource = Helper::setArrayValue($resource, $secret['to'], $data);
                } catch (\Exception $e) {
                    throw new Exception\SecretNotResolvable('secret key '.$secret['key'].' is not resolveable');
                }
            }
        }

        return $resource;
    }

    /**
     * Reverse resolved secrets.
     */
    public static function reverse(ResourceInterface $resource, array $result): array
    {
        foreach ($resource->getSecrets() as $secret) {
            $result = Helper::deleteArrayValue($result, $secret['to']);
        }

        return $result;
    }

    /**
     * Delete by name.
     */
    public function deleteOne(ResourceNamespaceInterface $namespace, string $name): bool
    {
        $resource = $this->getOne($namespace, $name);

        return $this->resource_factory->deleteFrom($this->db->{self::COLLECTION_NAME}, $resource->getId());
    }

    /**
     * Update.
     */
    public function update(SecretInterface $resource, array $data): bool
    {
        $data['name'] = $resource->getName();
        $data['kind'] = $resource->getKind();
        $data = $this->resource_factory->validate($data);
        $data = $this->crypt($data);

        return $this->resource_factory->updateIn($this->db->{self::COLLECTION_NAME}, $resource, $data);
    }

    /**
     * Add secret.
     */
    public function add(ResourceNamespaceInterface $namespace, array $resource): ObjectIdInterface
    {
        $resource['kind'] = 'Secret';
        $resource = $this->resource_factory->validate($resource);

        if ($this->has($namespace, $resource['name'])) {
            throw new Exception\NotUnique('secret '.$resource['name'].' does already exists');
        }

        $resource = $this->crypt($resource);

        $resource['namespace'] = $namespace->getName();

        return $this->resource_factory->addTo($this->db->{self::COLLECTION_NAME}, $resource);
    }

    /**
     * Change stream.
     */
    public function watch(ResourceNamespaceInterface $namespace, ?ObjectIdInterface $after = null, bool $existing = true, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        $filter = $this->prepareQuery($namespace, $query);
        $that = $this;

        return $this->resource_factory->watchFrom($this->db->{self::COLLECTION_NAME}, $after, $existing, $filter, function (array $resource) use ($namespace, $that) {
            return $that->build($resource, $namespace);
        }, $offset, $limit, $sort);
    }

    /**
     * Build instance.
     */
    public function build(array $resource, ResourceNamespaceInterface $namespace): SecretInterface
    {
        $decrypted = json_decode(Symmetric::decrypt($resource['blob'], $this->key)->getString(), true);
        $resource['data'] = $decrypted;
        unset($resource['blob']);

        return $this->resource_factory->initResource(new Secret($resource, $namespace));
    }

    /**
     * Prepare query.
     */
    protected function prepareQuery(ResourceNamespaceInterface $namespace, ?array $query = null): array
    {
        $filter = [
            'namespace' => $namespace->getName(),
        ];

        if (!empty($query)) {
            $filter = [
                '$and' => [$filter, $query],
            ];
        }

        return $filter;
    }

    /**
     * Encrypt resource data.
     */
    protected function crypt(array $resource): array
    {
        if (KeyFactory::export($this->key)->getString() === self::DEFAULT_KEY) {
            throw new Exception\InvalidEncryptionKey('encryption key required to be changed');
        }

        $message = new HiddenString(json_encode($resource['data']));
        $resource['blob'] = Symmetric::encrypt($message, $this->key);
        unset($resource['data']);

        return $resource;
    }
}
