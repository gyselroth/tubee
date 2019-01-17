<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Workflow;

use Generator;
use MongoDB\BSON\ObjectIdInterface;
use MongoDB\Database;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Resource\Factory as ResourceFactory;
use Tubee\V8\Engine as V8Engine;
use Tubee\Workflow;

class Factory extends ResourceFactory
{
    /**
     * Collection name.
     */
    public const COLLECTION_NAME = 'workflows';

    /**
     * V8 engine.
     *
     * @var V8Engine
     */
    protected $v8;

    /**
     * Initialize.
     */
    public function __construct(Database $db, V8Engine $v8, LoggerInterface $logger)
    {
        parent::__construct($db, $logger);
        $this->v8 = $v8;
    }

    /**
     * Has namespace.
     */
    public function has(EndpointInterface $endpoint, string $name): bool
    {
        return $this->db->{self::COLLECTION_NAME}->count([
            'name' => $name,
            'namespace' => $endpoint->getCollection()->getResourceNamespace()->getName(),
            'collection' => $endpoint->getCollection()->getName(),
            'endpoint' => $endpoint->getName(),
        ]) > 0;
    }

    /**
     * Get all.
     */
    public function getAll(EndpointInterface $endpoint, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        $filter = [
            'namespace' => $endpoint->getCollection()->getResourceNamespace()->getName(),
            'collection' => $endpoint->getCollection()->getName(),
            'endpoint' => $endpoint->getName(),
        ];

        if (!empty($query)) {
            $filter = [
                '$and' => [$filter, $query],
            ];
        }

        return $this->getAllFrom($this->db->{self::COLLECTION_NAME}, $filter, $offset, $limit, $sort, function (array $resource) use ($endpoint) {
            return $this->build($resource, $endpoint);
        });
    }

    /**
     * Get one.
     */
    public function getOne(EndpointInterface $endpoint, string $name): WorkflowInterface
    {
        $result = $this->db->{self::COLLECTION_NAME}->findOne([
            'name' => $name,
            'namespace' => $endpoint->getCollection()->getResourceNamespace()->getName(),
            'collection' => $endpoint->getCollection()->getName(),
            'endpoint' => $endpoint->getName(),
        ], [
            'projection' => ['history' => 0],
        ]);

        if ($result === null) {
            throw new Exception\NotFound('workflow '.$name.' is not registered');
        }

        return $this->build($result, $endpoint);
    }

    /**
     * Delete by name.
     */
    public function deleteOne(EndpointInterface $endpoint, string $name): bool
    {
        $resource = $this->getOne($endpoint, $name);

        return $this->deleteFrom($this->db->{self::COLLECTION_NAME}, $resource->getId());
    }

    /**
     * Add.
     */
    public function add(EndpointInterface $endpoint, array $resource): ObjectIdInterface
    {
        $resource = Validator::validateWorkflow($resource);

        if ($this->has($endpoint, $resource['name'])) {
            throw new Exception\NotUnique('workflow '.$resource['name'].' does already exists');
        }

        $resource['namespace'] = $endpoint->getCollection()->getResourceNamespace()->getName();
        $resource['collection'] = $endpoint->getCollection()->getName();
        $resource['endpoint'] = $endpoint->getName();

        return $this->addTo($this->db->{self::COLLECTION_NAME}, $resource);
    }

    /**
     * Update.
     */
    public function update(WorkflowInterface $resource, array $data): bool
    {
        $data['name'] = $resource->getName();
        $data = Validator::validateWorkflow($data);

        return $this->updateIn($this->db->{self::COLLECTION_NAME}, $resource, $data);
    }

    /**
     * Change stream.
     */
    public function watch(EndpointInterface $endpoint, ?ObjectIdInterface $after = null, bool $existing = true, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        return $this->watchFrom($this->db->{self::COLLECTION_NAME}, $after, $existing, $query, function (array $resource) use ($endpoint) {
            return $this->build($resource, $endpoint);
        }, $offset, $limit, $sort);
    }

    /**
     * Build instance.
     */
    public function build(array $resource, EndpointInterface $endpoint): WorkflowInterface
    {
        $map = new AttributeMap($resource['data']['map'], $this->v8, $this->logger);

        return $this->initResource(new Workflow($resource['name'], $resource['data']['ensure'], $this->v8, $map, $endpoint, $this->logger, $resource));
    }
}
