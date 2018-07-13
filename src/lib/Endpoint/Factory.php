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
use MongoDB\Database;
use Psr\Log\LoggerInterface;
use Tubee\DataType\DataTypeInterface;

class Factory
{
    /**
     * Database.
     *
     * @var Database
     */
    protected $db;

    /**
     * Datatype factory.
     *
     * @var DataTypeFactory
     */

    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Initialize.
     */
    public function __construct(Database $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
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
            yield (string) $resource['_id'] => new Endpoint($resource);
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

        return new Endpoint($result);
    }

    /**
     * Build instance.
     */
    public static function build(array $resource, DataTypeInterface $datatype, LoggerInterface $logger)
    {
        return new Endpoint($resource, $datatype, $logger);
    }
}
