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
            'name' => $name,
            'mandator' => $mandator->getName(),
        ]) > 0;
    }

    /**
     * Get all.
     */
    public function getAll(MandatorInterface $mandator, ?array $query = null, ?int $offset = null, ?int $limit = null): Generator
    {
        $filter = [
            'mandator' => $datatype->getMandator()->getName(),
        ];

        if (!empty($query)) {
            $filter = [
                '$and' => [$filter, $query],
            ];
        }

        $result = $this->db->datatypes->find($filter, [
            'offset' => $offset,
            'limit' => $limit,
        ]);

        foreach ($result as $resource) {
            yield (string) $resource['name'] => self::build($resource, $mandator, $this->db, $this->endpoint, $this->logger);
        }

        return $this->db->datatypes->count((array) $query);
    }

    /**
     * Get one.
     */
    public function getOne(MandatorInterface $mandator, string $name): DataTypeInterface
    {
        $result = $this->db->datatypes->findOne([
            'name' => $name,
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
            'name' => $name,
        ]);

        return true;
    }

    /**
     * Add mandator.
     */
    public function add(MandatorInterface $mandator, array $resource): ObjectId
    {
        $resource = DataTypeValidator::validate($resource);

        if ($this->has($mandator, $resource['name'])) {
            throw new Exception\NotUnique('datatype '.$resource['name'].' does already exists');
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
        $schema = new Schema($resource['schema'], $logger);

        return new DataType($resource['name'], $mandator, $endpoint, $schema, $db, $logger, $resource);
    }
}
