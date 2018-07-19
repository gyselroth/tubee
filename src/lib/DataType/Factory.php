<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\DataType;

use Generator;
use MongoDB\BSON\ObjectId;
use MongoDB\Database;
use Psr\Log\LoggerInterface;
use Tubee\DataType;
use Tubee\DataType\Validator as DataTypeValidator;
use Tubee\Endpoint\Factory as EndpointFactory;
use Tubee\Mandator\MandatorInterface;
use Tubee\Resource\Validator as ResourceValidator;
use Tubee\Schema;

class Factory
{
    /**
     * Database.
     *
     * @var Database
     */
    protected $db;

    /**
     * Logger.
     */
    protected $logger;

    /**
     * Endpoint.
     *
     * @var EndpointInterface
     */
    protected $endpoint;

    /**
     * Initialize.
     */
    public function __construct(Database $db, EndpointFactory $endpoint, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->endpoint = $endpoint;
    }

    /**
     * Has mandator.
     */
    public function has(MandatorInterface $mandator, string $name): bool
    {
        return $this->db->datatypes->count([
            'metadata.name' => $name,
            'mandator' => $mandator->getName(),
        ]) > 0;
    }

    /**
     * Get all.
     */
    public function getAll(MandatorInterface $mandator, ?array $query = null, ?int $offset = null, ?int $limit = null): Generator
    {
        $result = $this->db->datatypes->find(['mandator' => $mandator->getName()], [
            'offset' => $offset,
            'limit' => $limit,
        ]);

        foreach ($result as $resource) {
            yield (string) $resource['metadata']['name'] => self::build($resource, $mandator, $this->db, $this->endpoint, $this->logger);
        }

        return $this->db->datatypes->count((array) $query);
    }

    /**
     * Get one.
     */
    public function getOne(MandatorInterface $mandator, string $name): DataTypeInterface
    {
        $result = $this->db->datatypes->findOne([
            'metadata.name' => $name,
            'mandator' => $mandator->getName(),
        ]);

        if ($result === null) {
            throw new Exception\NotFound('mandator '.$name.' is not registered');
        }

        return self::build($result, $mandator, $this->db, $this->endpoint, $this->logger);
    }

    /**
     * Delete by name.
     */
    public function delete(MandatorInterface $mandator, string $name): bool
    {
        if (!$this->has($mandator, $name)) {
            throw new Exception\NotFound('endpoint '.$name.' does not exists');
        }

        $this->db->datatypes->deleteOne([
            'mandator' => $mandator->getName(),
            'metadata.name' => $name,
        ]);

        return true;
    }

    /**
     * Add mandator.
     */
    public function add(MandatorInterface $mandator, array $resource): ObjectId
    {
        ResourceValidator::validate($resource);
        DataTypeValidator::validate((array) $resource['spec']);

        if ($this->has($mandator, $resource['metadata']['name'])) {
            throw new Exception\NotUnique('datatype '.$resource['metadata']['name'].' does already exists');
        }

        $resource['mandator'] = $mandator->getName();
        $result = $this->db->datatypes->insertOne($resource);

        return $result->getInsertedId();
    }

    /**
     * Build instance.
     */
    public static function build(array $resource, MandatorInterface $mandator, Database $db, EndpointFactory $endpoint, LoggerInterface $logger): DataTypeInterface
    {
        $schema = new Schema($resource['spec']['schema'], $logger);

        return new DataType($resource['metadata']['name'], $mandator, $endpoint, $schema, $db, $logger, $resource);
    }
}
