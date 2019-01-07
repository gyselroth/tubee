<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use Generator;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\Collection\CollectionInterface;
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
    public function getAll(?array $query = null): Generator
    {
        $filter = $this->transformQuery($query);

        if ($filter === null) {
            $sql = 'SELECT * FROM '.$this->table;
        } else {
            $sql = 'SELECT * FROM '.$this->table; // .' WHERE '.$filter;
        }

        $result = $this->socket->select($sql);

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
        $filter = $this->getFilterOne($object);
        $sql = 'SELECT * FROM '.$this->table.' WHERE '.$filter;
        $result = $this->socket->select($sql);

        if ($result->num_rows > 1) {
            throw new Exception\ObjectMultipleFound('found more than one object with filter '.$filter);
        }
        if ($result->num_rows === 0) {
            throw new Exception\ObjectNotFound('no object found with filter '.$filter);
        }

        return $this->build($result->fetch_assoc());
    }

    /**
     * {@inheritdoc}
     */
    public function create(AttributeMapInterface $map, array $object, bool $simulate = false): ?string
    {
        $result = $this->prepareCreate($object, $simulate);

        if ($simulate === true) {
            return null;
        }

        return $this->socket->lastInsertId();
    }
}
