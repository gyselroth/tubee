<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2021 gyselroth GmbH (https://gyselroth.com)
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

class Factory
{
    /**
     * Collection name.
     */
    public const COLLECTION_NAME = 'workflows';

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
     * V8 engine.
     *
     * @var V8Engine
     */
    protected $v8;

    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Initialize.
     */
    public function __construct(Database $db, ResourceFactory $resource_factory, V8Engine $v8, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->resource_factory = $resource_factory;
        $this->v8 = $v8;
        $this->logger = $logger;
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
        $filter = $this->prepareQuery($endpoint, $query);
        $that = $this;

        return $this->resource_factory->getAllFrom($this->db->{self::COLLECTION_NAME}, $filter, $offset, $limit, $sort, function (array $resource) use ($endpoint, $that) {
            return $that->build($resource, $endpoint);
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

        return $this->resource_factory->deleteFrom($this->db->{self::COLLECTION_NAME}, $resource->getId());
    }

    /**
     * Add.
     */
    public function add(EndpointInterface $endpoint, array $resource): ObjectIdInterface
    {
        if (!isset($resource['kind'])) {
            $resource['kind'] = 'Workflow';
        }

        $resource = $this->resource_factory->validate($resource);

        if ($this->has($endpoint, $resource['name'])) {
            throw new Exception\NotUnique('workflow '.$resource['name'].' does already exists');
        }

        $resource['namespace'] = $endpoint->getCollection()->getResourceNamespace()->getName();
        $resource['collection'] = $endpoint->getCollection()->getName();
        $resource['endpoint'] = $endpoint->getName();

        return $this->resource_factory->addTo($this->db->{self::COLLECTION_NAME}, $resource);
    }

    /**
     * Update.
     */
    public function update(WorkflowInterface $resource, array $data): bool
    {
        $data['name'] = $resource->getName();
        $data['kind'] = $resource->getKind();
        $data = $this->resource_factory->validate($data);

        return $this->resource_factory->updateIn($this->db->{self::COLLECTION_NAME}, $resource, $data);
    }

    /**
     * Change stream.
     */
    public function watch(EndpointInterface $endpoint, ?ObjectIdInterface $after = null, bool $existing = true, ?array $query = null, ?int $offset = null, ?int $limit = null, ?array $sort = null): Generator
    {
        $filter = $this->prepareQuery($endpoint, $query);
        $that = $this;

        return $this->resource_factory->watchFrom($this->db->{self::COLLECTION_NAME}, $after, $existing, $filter, function (array $resource) use ($endpoint, $that) {
            return $that->build($resource, $endpoint);
        }, $offset, $limit, $sort);
    }

    /**
     * Build instance.
     */
    public function build(array $resource, EndpointInterface $endpoint): WorkflowInterface
    {
        $map = new AttributeMap($resource['data']['map'], $this->v8, $this->logger);

        switch ($endpoint->getType()) {
            case EndpointInterface::TYPE_SOURCE:
                $class = ImportWorkflow::class;

            break;
            case EndpointInterface::TYPE_DESTINATION:
                $class = ExportWorkflow::class;

            break;
            default:
            case EndpointInterface::TYPE_BROWSE:
                $class = Workflow::class;

            break;
        }

        return $this->resource_factory->initResource(new $class($resource['name'], $resource['data']['ensure'], $this->v8, $map, $endpoint, $this->logger, $resource));
    }

    /**
     * Prepare query.
     */
    protected function prepareQuery(EndpointInterface $endpoint, ?array $query = null): array
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

        return $filter;
    }
}
