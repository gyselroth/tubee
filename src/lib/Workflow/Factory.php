<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Workflow;

use MongoDB\Database;
use Tubee\Workflow\Exception;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap;
use MongoDB\BSON\ObjectId;

class Factory
{
    /**
     * Database
     *
     * @var Database
     */
    protected $db;

    /**
     * Expression lang
     *
     * @var ExpressionLanguage
     */
    protected $script;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Initialize.
     */
    public function __construct(Database $db, ExpressionLanguage $script, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->script = $script;
    }

    /**
     * Has mandator.
     */
    public function has(EndpointInterface $endpoint, string $name): bool
    {
        return $this->db->workflows->count([
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
        $result = $this->db->datatypes->find([
            'mandator' => $endpoint->getDataType()->getMandator()->getName(),
            'datatype' => $endpoint->getDataType()->getName(),
            'endpoint' => $endpoint->getName(),
        ], [
            'offset' => $offset,
            'limit' => $limit,
        ]);

        foreach ($result as $resource) {
            yield (string) $resource['_id'] => self::build($resource, $endpoint, $this->db, $this->logger);
        }

        return $this->db->datatypes->count((array) $query);
    }

    /**
     * Get one.
     */
    public function getOne(EndpointInterface $endpoint, string $name): WorkflowInterface
    {
        $result = $this->db->datatypes->findOne([
            'name' => $name,
            'mandator' => $endpoint->getDataType()->getMandator()->getName(),
            'datatype' => $endpoint->getDataType()->getName(),
            'endpoint' => $endpoint->getName(),
        ]);

        if ($result === null) {
            throw new Exception\NotFound('workflow '.$name.' is not registered');
        }

        return self::build($result, $endpoint, $this->db, $this->logger);
    }

    /**
     * Delete by name.
     */
    public function delete(EndpointInterface $endpoint, string $name): bool
    {
        if (!$this->has($endpoint, $name)) {
            throw new Exception\NotFound('workflow '.$name.' does not exists');
        }

        $this->db->datatypes->deleteOne([
            'mandator' => $endpoint->getDataType()->getMandator()->getName(),
            'datatype' => $endpoint->getDataType()->getName(),
            'endpoint' => $endpoint->getName(),
            'name' => $name,
        ]);

        return true;
    }

    /**
     * Add mandator.
     */
    public function add(EndpointInterface $endpoint, array $resource): ObjectId
    {
        Validator::validate($resource);

        if ($this->has($endpoint, $resource['name'])) {
            throw new Exception\NotUnique('datatype '.$resource['name'].' does already exists');
        }

        $resource['mandator'] = $endpoint->getDataType()->getMandator()->getName(),
        $resource['datatype'] = $endpoint->getDataType()->getName(),
        $resource['endpoint'] = $endpoint->getName(),
        $result = $this->db->workflows->insertOne($resource);

        return $result->getInsertedId();
    }

    /**
     * Build instance
     */
    public static function build(array $resource, EndpointInterface $endpoint, Database $db, LoggerInterface $logger): WorkflowInterface
    {
        $schema = new AttributeMap($resource['map']);
        return new Workflow($resource, $endpoint, $script, $map, $db, $logger);
    }
}
