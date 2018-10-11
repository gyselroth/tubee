<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Workflow;

use Generator;
use MongoDB\BSON\ObjectId;
use MongoDB\Database;
use Psr\Log\LoggerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Tubee\AttributeMap;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Resource\Factory as ResourceFactory;
use Tubee\Workflow;

class Factory extends ResourceFactory
{
    /**
     * Collection name.
     */
    public const COLLECTION_NAME = 'workflows';

    /**
     * Expression lang.
     *
     * @var ExpressionLanguage
     */
    protected $script;

    /**
     * Initialize.
     */
    public function __construct(Database $db, ExpressionLanguage $script, LoggerInterface $logger)
    {
        parent::__construct($db, $logger);
        $this->script = $script;
    }

    /**
     * Has mandator.
     */
    public function has(EndpointInterface $endpoint, string $name): bool
    {
        return $this->db->{self::COLLECTION_NAME}->count([
            'name' => $name,
            'mandator' => $endpoint->getDataType()->getMandator()->getName(),
            'datatype' => $endpoint->getDataType()->getName(),
            'endpoint' => $endpoint->getName(),
        ]) > 0;
    }

    /**
     * Get all.
     */
    public function getAll(EndpointInterface $endpoint, ?array $query = null, ?int $offset = null, ?int $limit = null): Generator
    {
        $filter = [
            'mandator' => $endpoint->getDataType()->getMandator()->getName(),
            'datatype' => $endpoint->getDataType()->getName(),
            'endpoint' => $endpoint->getName(),
        ];

        if (!empty($query)) {
            $filter = [
                '$and' => [$filter, $query],
            ];
        }

        $result = $this->db->{self::COLLECTION_NAME}->find($filter, [
            'offset' => $offset,
            'limit' => $limit,
        ]);

        foreach ($result as $resource) {
            yield (string) $resource['name'] => $this->build($resource, $endpoint);
        }

        return $this->db->{self::COLLECTION_NAME}->count((array) $query);
    }

    /**
     * Get one.
     */
    public function getOne(EndpointInterface $endpoint, string $name): WorkflowInterface
    {
        $result = $this->db->{self::COLLECTION_NAME}->findOne([
            'name' => $name,
            'mandator' => $endpoint->getDataType()->getMandator()->getName(),
            'datatype' => $endpoint->getDataType()->getName(),
            'endpoint' => $endpoint->getName(),
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

        return $this->deleteFrom($this->db->endpoints, $resource->getId());
    }

    /**
     * Add.
     */
    public function add(EndpointInterface $endpoint, array $resource): ObjectId
    {
        $resource = Validator::validate($resource);

        if ($this->has($endpoint, $resource['name'])) {
            throw new Exception\NotUnique('workflow '.$resource['name'].' does already exists');
        }

        $resource['mandator'] = $endpoint->getDataType()->getMandator()->getName();
        $resource['datatype'] = $endpoint->getDataType()->getName();
        $resource['endpoint'] = $endpoint->getName();

        return $this->addTo($this->db->{self::COLLECTION_NAME}, $resource);
    }

    /**
     * Update.
     */
    public function update(WorkflowInterface $resource, array $data): bool
    {
        $data = Validator::validate($data);

        return $this->updateIn($this->db->{self::COLLECTION_NAME}, $resource->getId(), $data);
    }

    /**
     * Build instance.
     */
    public function build(array $resource, EndpointInterface $endpoint): WorkflowInterface
    {
        $map = new AttributeMap($resource['map'], $this->script, $this->logger);

        return $this->initResource(new Workflow($resource['name'], $resource['ensure'], $this->script, $map, $endpoint, $this->logger, $resource));
    }
}
