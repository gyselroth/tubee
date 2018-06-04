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

abstract class AbstractSqlDatabase extends AbstractEndpoint
{
    /**
     * Resource.
     *
     * @var resource
     */
    protected $resource;

    /**
     * Primary table.
     *
     * @var string
     */
    protected $table;

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

        $this->resource->query('TRUNCATE `'.$this->table.'`;');

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
            $this->resource->prepare($query, $values);
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
            $this->resource->query($sql);
        }

        return true;
    }

    /**
     * Prepare.
     *
     * @param iterable $object
     * @param bool     $simulate
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
            return $this->resource->prepare($sql, $values);
        }

        return null;
    }

    /**
     * Build filter.
     *
     * @param string $filter
     *
     * @return string
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
                    $request .= '`'.$attr.'`="'.$value.'"';
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

    /**
     * Build filter all.
     *
     * @param string $filter
     *
     * @return string
     */
    protected function buildFilterAll($filter): ?string
    {
        $filter = $this->buildFilter($filter);
        $all = $this->buildFilter($this->filter_all);

        if ($filter !== null && $all !== null) {
            return '('.$all.') AND ('.$filter.')';
        }
        if ($filter !== null) {
            return $filter;
        }
        if ($all !== null) {
            return $all;
        }

        return null;
    }
}
