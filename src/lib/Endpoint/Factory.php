<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use Generator;
use MongoDB\BSON\ObjectId;
use MongoDB\Database;
use Psr\Log\LoggerInterface;
use Tubee\DataType\DataTypeInterface;
use Tubee\Workflow\Factory as WorkflowFactory;

class Factory
{
    /**
     * Database.
     *
     * @var Database
     */
    protected $db;

    /**
     * Factory.
     *
     * @var WorkflowFactory
     */
    protected $workflow;

    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Initialize.
     */
    public function __construct(Database $db, WorkflowFactory $workflow, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->workflow = $workflow;
    }

    /**
     * Has endpoint.
     */
    public function has(DataTypeInterface $datatype, string $name): bool
    {
        return $this->db->endpoints->count([
            'name' => $name,
            'mandator' => $datatype->getMandator()->getName(),
            'datatype' => $datatype->getName(),
        ]) > 0;
    }

    /**
     * Get all.
     */
    public function getAll(DataTypeInterface $datatype, ?array $query = null, ?int $offset = null, ?int $limit = null): Generator
    {
        $result = $this->db->endpoints->find([
            'mandator' => $datatype->getMandator()->getName(),
            'datatype' => $datatype->getName(),
        ], [
            'offset' => $offset,
            'limit' => $limit,
        ]);

        foreach ($result as $resource) {
            yield (string) $resource['name'] => self::build($resource, $datatype, $this->workflow, $this->logger);
        }

        return $this->db->endpoints->count((array) $query);
    }

    /**
     * Get one.
     */
    public function getOne(DataTypeInterface $datatype, string $name): EndpointInterface
    {
        $result = $this->db->endpoints->findOne([
            'name' => $name,
            'mandator' => $datatype->getMandator()->getName(),
            'datatype' => $datatype->getName(),
        ]);

        if ($result === null) {
            throw new Exception\DataTypeNotFound('mandator '.$name.' is not registered');
        }

        return self::build($result, $datatype, $this->workflow, $this->logger);
    }

    /**
     * Delete by name.
     */
    public function delete(DataTypeInterface $datatype, string $name): bool
    {
        if (!$this->has($datatype, $name)) {
            throw new Exception\NotFound('endpoint '.$name.' does not exists');
        }

        $this->db->endpoints->deleteOne([
            'name' => $name,
            'mandator' => $datatype->getMandator()->getName(),
            'datatype' => $datatype->getName(),
        ]);

        return true;
    }

    /**
     * Add mandator.
     */
    public function add(DataTypeInterface $datatype, array $resource): ObjectId
    {
        $resource = Validator::validate($resource);

        if ($this->has($datatype, $resource['name'])) {
            throw new Exception\NotUnique('endpoint '.$resource['name'].' does already exists');
        }

        $endpoint = self::build($resource, $datatype, $this->logger);
        $endpoint->setup();

        $resource['mandator'] = $datatype->getMandator()->getName();
        $resource['datatype'] = $datatype->getName();
        $result = $this->db->endpoints->insertOne($resource);

        return $result->getInsertedId();
    }

    /**
     * Build instance.
     */
    public static function build(array $resource, DataTypeInterface $datatype, WorkflowFactory $workflow, LoggerInterface $logger)
    {
        $factory = $resource['class'].'\\Factory';

        return $factory::build($resource, $datatype, $workflow, $logger);
    }
}
