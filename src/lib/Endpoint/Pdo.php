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
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\DataType\DataTypeInterface;
use Tubee\Endpoint\Pdo\Wrapper as PdoWrapper;

class Pdo extends AbstractSqlDatabase
{
    /**
     * Init endpoint.
     */
    public function __construct(array $resource, PdoWrapper $pdo, DataTypeInterface $datatype, LoggerInterface $logger)
    {
        $this->resource = $resource;
        $this->socket = $pdo;
        $this->table = $resource['table'];
        parent::__construct($resource, $datatype, $logger, $resource['config']);
    }

    /**
     * {@inheritdoc}
     */
    public function getAll($filter = null): Generator
    {
        $filter = $this->buildFilterAll($filter);

        if ($filter === null) {
            $sql = 'SELECT * FROM '.$this->table;
        } else {
            $sql = 'SELECT * FROM '.$this->table.' WHERE '.$filter;
        }

        $result = $this->socket->select($sql);

        while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOne(Iterable $object, Iterable $attributes = []): Iterable
    {
        $filter = $this->getFilterOne($object);
        $sql = 'SELECT * FROM '.$this->table.' WHERE '.$filter;
        $result = $this->socket->select($sql);

        if ($result->num_rows > 1) {
            throw new Exception\ObjectMultipleFound('found more than one object with filter '.$filter);
        }
        if ($result->num_rows === 0) {
            throw new Exception\ObjectNotFound('no object found with filter '.$filter);
        }

        return $result->fetch_assoc();
    }

    /**
     * {@inheritdoc}
     */
    public function create(AttributeMapInterface $map, Iterable $object, bool $simulate = false): ?string
    {
        $result = $this->prepareCreate($object, $simulate);

        if ($simulate === true) {
            return null;
        }

        return $this->socket->getResouce()->lastInsertId();
    }
}
