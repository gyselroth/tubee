<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use InvalidArgumentException;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\Endpoint\Pdo\QueryTransformer;

abstract class AbstractSqlDatabase extends AbstractEndpoint
{
    /**
     * Resource.
     *
     * @var array
     */
    protected $resource = [];

    /**
     * Socket.
     */
    protected $socket;

    /**
     * Primary table.
     *
     * @var string
     */
    protected $table;

    /**
     * {@inheritdoc}
     */
    public function setup(bool $simulate = false): EndpointInterface
    {
        $this->socket->connect();
        //$result = $this->socket->select('SELECT count(*) FROM '.$this->table);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(bool $simulate = false): bool
    {
        $this->logger->info('flush table ['.$this->table.'] from endpoint ['.$this->name.']', [
            'category' => get_class($this),
        ]);

        if ($simulate === true) {
            return true;
        }

        $this->socket->query('TRUNCATE `'.$this->table.'`;');

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getDiff(AttributeMapInterface $map, array $diff): array
    {
        $result = [];
        foreach ($diff as $attribute => $update) {
            switch ($update['action']) {
                case AttributeMapInterface::ACTION_REPLACE:
                    $result[$attribute] = '`'.$attribute.'` = ?';

                break;
                case AttributeMapInterface::ACTION_REMOVE:
                    $result[$attribute] = '`'.$attribute.'` = NULL';

                break;
                default:
                    throw new InvalidArgumentException('unknown diff action '.$update['action'].' given');
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function change(AttributeMapInterface $map, Iterable $diff, Iterable $object, Iterable $endpoint_object, bool $simulate = false): ?string
    {
        $values = array_intersect_key($object, $diff);
        $filter = $this->getFilterOne($object);
        $query = 'UPDATE '.$this->table.' SET '.implode(',', $diff).' WHERE '.$filter;

        if ($simulate === false) {
            $this->socket->prepareValues($query, $values);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(AttributeMapInterface $map, Iterable $object, Iterable $endpoint_object, bool $simulate = false): bool
    {
        $filter = $this->getFilterOne($object);
        $sql = 'DELETE FROM '.$this->table.' WHERE '.$filter;

        if ($simulate === false) {
            $this->socket->query($sql);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function transformQuery(?array $query = null)
    {
        $result = null;
        if ($this->filter_all !== null) {
            $result = $this->filter_all;
        }

        if (!empty($query)) {
            if ($this->filter_all === null) {
                $result = QueryTransformer::transform($query);
            } else {
                $result = '('.$this->filter_all.') AND ('.QueryTransformer::transform($query).')';
            }
        }

        return $result;
    }

    /**
     * Prepare.
     */
    protected function prepareCreate(Iterable $object, bool $simulate = false)
    {
        $columns = [];
        $values = [];
        $repl = [];

        foreach ($object as $column => $value) {
            if (is_array($value)) {
                throw new Exception\EndpointCanNotHandleArray('endpoint can not handle array as a value ["'.$column.'"]. did you forget to set a decorator?');
            }

            $columns[] = '`'.$column.'`';
            $repl[] = '?';
            $values[] = $value;
        }

        $sql = 'INSERT INTO '.$this->table.' ('.implode(',', $columns).') VALUES ('.implode(',', $repl).')';

        if ($simulate === false) {
            return $this->socket->prepareValues($sql, $values);
        }

        return null;
    }

    /**
     * Build filter.
     */
    protected function buildFilter($filter): ?string
    {
        if (is_iterable($filter)) {
            if (count($filter) > 0) {
                $request = '';
                $i = 0;
                foreach ($filter as $attr => $value) {
                    if ($i !== 0) {
                        $request .= 'AND ';
                    }
                    $request .= $attr.'=\''.$value.'\'';
                    ++$i;
                }

                return $request;
            }

            return null;
        }
        if ($filter === null) {
            return null;
        }

        return $filter;
    }
}
