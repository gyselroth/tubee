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
use Tubee\Workflow\Validator as WorkflowValidator;

class Factory extends ResourceFactory
{
    /**
     * Database.
     *
     * @var Database
     */
    protected $db;

    /**
     * Expression lang.
     *
     * @var ExpressionLanguage
     */
    protected $script;

    /**
     * Logger.
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

        $result = $this->db->workflows->find($filter, [
            'offset' => $offset,
            'limit' => $limit,
        ]);

        foreach ($result as $resource) {
            yield (string) $resource['name'] => self::build($resource, $endpoint, $this->script, $this->logger);
        }

        return $this->db->workflows->count((array) $query);
    }

    /**
     * Get one.
     */
    public function getOne(EndpointInterface $endpoint, string $name): WorkflowInterface
    {
        $result = $this->db->workflows->findOne([
            'name' => $name,
            'mandator' => $endpoint->getDataType()->getMandator()->getName(),
            'datatype' => $endpoint->getDataType()->getName(),
            'endpoint' => $endpoint->getName(),
        ]);

        if ($result === null) {
            throw new Exception\NotFound('workflow '.$name.' is not registered');
        }

        return self::build($result, $endpoint, $this->script, $this->logger);
    }

    /**
     * Delete by name.
     */
    public function delete(EndpointInterface $endpoint, string $name): bool
    {
        if (!$this->has($endpoint, $name)) {
            throw new Exception\NotFound('workflow '.$name.' does not exists');
        }

        $this->db->workflows->deleteOne([
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
        $resource = WorkflowValidator::validate($resource);

        if ($this->has($endpoint, $resource['name'])) {
            throw new Exception\NotUnique('workflow '.$resource['name'].' does already exists');
        }

        $resource['mandator'] = $endpoint->getDataType()->getMandator()->getName();
        $resource['datatype'] = $endpoint->getDataType()->getName();
        $resource['endpoint'] = $endpoint->getName();

        return parent::addTo($this->db->workflows, $resource);
    }

    /**
     * Build instance.
     */
    public static function build(array $resource, EndpointInterface $endpoint, ExpressionLanguage $script, LoggerInterface $logger): WorkflowInterface
    {
        $map = new AttributeMap($resource['map'], $script, $logger);

        return new Workflow($resource['name'], $resource['ensure'], $script, $map, $endpoint, $logger, $resource);
    }
}
