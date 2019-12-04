<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use Generator;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\Pdo\Exception\InvalidQuery;
use Tubee\Endpoint\Pdo\Wrapper as PdoWrapper;
use Tubee\EndpointObject\EndpointObjectInterface;
use Tubee\Workflow\Factory as WorkflowFactory;

class Pdo extends AbstractSqlDatabase
{
    /**
     * Kind.
     */
    public const KIND = 'PdoEndpoint';

    /**
     * Init endpoint.
     */
    public function __construct(string $name, string $type, string $table, PdoWrapper $socket, CollectionInterface $collection, WorkflowFactory $workflow, LoggerInterface $logger, array $resource = [])
    {
        $this->socket = $socket;
        $this->table = $table;
        parent::__construct($name, $type, $collection, $workflow, $logger, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function count(?array $query = null): int
    {
        list($filter, $values) = $this->transformQuery($query);

        if ($filter === null) {
            $sql = 'SELECT COUNT(*) as count FROM '.$this->table;
        } else {
            $sql = 'SELECT COUNT(*) as count FROM '.$this->table.' WHERE '.$filter;
        }

        try {
            $result = $this->socket->prepareValues($sql, $values);

            return (int) $result->fetch(\PDO::FETCH_ASSOC)['count'];
        } catch (InvalidQuery $e) {
            $this->logger->error('failed to count number of objects from endpoint', [
                'class' => get_class($this),
                'exception' => $e,
            ]);

            return 0;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(?array $query = null): Generator
    {
        list($filter, $values) = $this->transformQuery($query);
        $this->logGetAll($filter);

        if ($filter === null) {
            $sql = 'SELECT * FROM '.$this->table;
        } else {
            $sql = 'SELECT * FROM '.$this->table.' WHERE '.$filter;
        }

        try {
            $result = $this->socket->prepareValues($sql, $values);
        } catch (InvalidQuery $e) {
            $this->logger->error('failed to fetch resources from endpoint', [
                'class' => get_class($this),
                'exception' => $e,
            ]);

            return 0;
        }

        $i = 0;
        while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {
            yield $this->build($row);
            ++$i;
        }

        return $i;
    }

    /**
     * {@inheritdoc}
     */
    public function getOne(array $object, array $attributes = []): EndpointObjectInterface
    {
        list($filter, $values) = $query = $this->transformQuery($this->getFilterOne($object));
        $this->logGetOne($filter);
        $sql = 'SELECT * FROM '.$this->table.' WHERE '.$filter;
        $result = $this->socket->prepareValues($sql, $values);
        $row = $result->fetch(\PDO::FETCH_ASSOC);
        $seccond = $result->fetch(\PDO::FETCH_ASSOC);

        if ($seccond !== false) {
            throw new Exception\ObjectMultipleFound('found more than one object with filter '.$filter);
        }
        if ($row === false) {
            throw new Exception\ObjectNotFound('no object found with filter '.$filter);
        }

        return $this->build($row, $query);
    }

    /**
     * {@inheritdoc}
     */
    public function create(AttributeMapInterface $map, array $object, bool $simulate = false): ?string
    {
        $this->logCreate($object);
        $result = $this->prepareCreate($object, $simulate);

        if ($simulate === true) {
            return null;
        }

        return $this->socket->lastInsertId();
    }
}
