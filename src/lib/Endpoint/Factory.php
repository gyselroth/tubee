<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use Generator;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\ObjectIdInterface;
use MongoDB\Database;
use Psr\Log\LoggerInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Helper;
use Tubee\Resource\Factory as ResourceFactory;
use Tubee\Secret\Factory as SecretFactory;
use Tubee\Workflow\Factory as WorkflowFactory;

class Factory extends ResourceFactory
{
    /**
     * Collection name.
     */
    public const COLLECTION_NAME = 'endpoints';

    /**
     * Factory.
     *
     * @var WorkflowFactory
     */
    protected $workflow_factory;

    /**
     * Secret factory.
     *
     * @var SecretFactory
     */
    protected $secret_factory;

    /**
     * Initialize.
     */
    public function __construct(Database $db, WorkflowFactory $workflow_factory, SecretFactory $secret_factory, LoggerInterface $logger)
    {
        $this->workflow_factory = $workflow_factory;
        $this->secret_factory = $secret_factory;
        parent::__construct($db, $logger);
    }

    /**
     * Has endpoint.
     */
    public function has(CollectionInterface $collection, string $name): bool
    {
        return $this->db->{self::COLLECTION_NAME}->count([
            'name' => $name,
            'namespace' => $collection->getResourceNamespace()->getName(),
            'collection' => $collection->getName(),
        ]) > 0;
    }

    /**
     * Get all.
     */
    public function getAll(CollectionInterface $collection, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        $filter = [
            'namespace' => $collection->getResourceNamespace()->getName(),
            'collection' => $collection->getName(),
        ];

        if (!empty($query)) {
            $filter = [
                '$and' => [$filter, $query],
            ];
        }

        return $this->getAllFrom($this->db->{self::COLLECTION_NAME}, $filter, $offset, $limit, $sort, function (array $resource) use ($collection) {
            return $this->build($resource, $collection);
        });
    }

    /**
     * Get one.
     */
    public function getOne(CollectionInterface $collection, string $name): EndpointInterface
    {
        $result = $this->db->{self::COLLECTION_NAME}->findOne([
            'name' => $name,
            'namespace' => $collection->getResourceNamespace()->getName(),
            'collection' => $collection->getName(),
        ], [
            'projection' => ['history' => 0],
        ]);

        if ($result === null) {
            throw new Exception\NotFound('endpoint '.$name.' is not registered');
        }

        return $this->build($result, $collection);
    }

    /**
     * Delete by name.
     */
    public function deleteOne(CollectionInterface $collection, string $name): bool
    {
        $resource = $this->getOne($collection, $name);

        return $this->deleteFrom($this->db->{self::COLLECTION_NAME}, $resource->getId());
    }

    /**
     * Add.
     */
    public function add(CollectionInterface $collection, array $resource): ObjectIdInterface
    {
        $resource = $this->secret_factory->resolve($collection->getResourceNamespace(), $resource);
        $resource = $this->validate($resource);
        $resource = Validator::validate($resource);

        foreach ($resource['secrets'] as $secret) {
            $resource = Helper::deleteArrayValue($resource, $secret['to']);
        }

        if ($this->has($collection, $resource['name'])) {
            throw new Exception\NotUnique('endpoint '.$resource['name'].' does already exists');
        }

        $resource['_id'] = new ObjectId();
        $endpoint = $this->build($resource, $collection);
        $endpoint->setup();

        if ($resource['data']['type'] === EndpointInterface::TYPE_SOURCE) {
            $this->ensureIndex($collection, $resource['data']['options']['import']);
        }

        $resource['namespace'] = $collection->getResourceNamespace()->getName();
        $resource['collection'] = $collection->getName();

        return $this->addTo($this->db->{self::COLLECTION_NAME}, $resource);
    }

    /**
     * Update.
     */
    public function update(EndpointInterface $resource, array $data): bool
    {
        $data['name'] = $resource->getName();
        $data['kind'] = $resource->getKind();

        $data = $this->secret_factory->resolve($resource->getCollection()->getResourceNamespace(), $data);
        $data = $this->validate($data);
        $data = Validator::validate($data);

        foreach ($data['secrets'] as $secret) {
            $data = Helper::deleteArrayValue($data, $secret['to']);
        }

        $data['_id'] = $resource->getId();

        $endpoint = $this->build($data, $resource->getCollection());
        $endpoint->setup();

        return $this->updateIn($this->db->{self::COLLECTION_NAME}, $resource, $data);
    }

    /**
     * Change stream.
     */
    public function watch(CollectionInterface $collection, ?ObjectIdInterface $after = null, bool $existing = true, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        return $this->watchFrom($this->db->{self::COLLECTION_NAME}, $after, $existing, $query, function (array $resource) use ($collection) {
            return $this->build($resource, $collection);
        }, $offset, $limit, $sort);
    }

    /**
     * Build instance.
     */
    public function build(array $resource, CollectionInterface $collection)
    {
        $factory = EndpointInterface::ENDPOINT_MAP[$resource['kind']].'\\Factory';
        $resource = $this->secret_factory->resolve($collection->getResourceNamespace(), $resource);

        return $this->initResource($factory::build($resource, $collection, $this->workflow_factory, $this->logger));
    }

    /**
     * Ensure indexes.
     */
    protected function ensureIndex(CollectionInterface $collection, array $fields): string
    {
        $list = iterator_to_array($this->db->{$collection->getCollection()}->listIndexes());
        $keys = array_fill_keys($fields, 1);

        $this->logger->debug('verify if mongodb index exists for import attributes [{import}]', [
            'category' => get_class($this),
            'import' => $keys,
        ]);

        foreach ($list as $index) {
            if ($index['key'] === $keys) {
                $this->logger->debug('found existing mongodb index ['.$index['name'].']', [
                    'category' => get_class($this),
                    'fields' => $keys,
                ]);

                return $index['name'];
            }
        }

        $this->logger->info('create new mongodb index', [
            'category' => get_class($this),
        ]);

        return $this->db->{$collection->getCollection()}->createIndex($keys);
    }
}
