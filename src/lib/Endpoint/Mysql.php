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
use Tubee\Endpoint\Mysql\Exception\InvalidQuery;
use Tubee\Endpoint\Mysql\Wrapper as MysqlWrapper;
use Tubee\EndpointObject\EndpointObjectInterface;
use Tubee\Workflow\Factory as WorkflowFactory;

class Mysql extends AbstractSqlDatabase
{
    /**
     * Kind.
     */
    public const KIND = 'MysqlEndpoint';

    /**
     * Init endpoint.
     */
    public function __construct(string $name, string $type, string $table, MysqlWrapper $socket, CollectionInterface $collection, WorkflowFactory $workflow, LoggerInterface $logger, array $resource = [])
    {
        $this->socket = $socket;
        $this->table = $table;
        parent::__construct($name, $type, $collection, $workflow, $logger, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown(bool $simulate = false): EndpointInterface
    {
        $this->socket->close();

        return $this;
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

            return (int) $result->get_result()->fetch_assoc()['count'];
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
        $result = $result->get_result();

        while ($row = $result->fetch_assoc()) {
            yield $this->build($row);
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
        $result = $result->get_result();

        if ($result->num_rows > 1) {
            throw new Exception\ObjectMultipleFound('found more than one object with filter '.$filter);
        }
        if ($result->num_rows === 0) {
            throw new Exception\ObjectNotFound('no object found with filter '.$filter);
        }

        return $this->build($result->fetch_assoc(), $query);
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

        return (string) $result->insert_id;
    }
}
