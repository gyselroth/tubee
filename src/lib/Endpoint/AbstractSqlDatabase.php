<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2021 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use InvalidArgumentException;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\Endpoint\Pdo\QueryTransformer;
use Tubee\EndpointObject\EndpointObjectInterface;

abstract class AbstractSqlDatabase extends AbstractEndpoint
{
    use LoggerTrait;

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
        $this->socket->initialize();
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
                    $result[$attribute] = QueryTransformer::filterField($attribute).' = ?';

                break;
                case AttributeMapInterface::ACTION_REMOVE:
                    $result[$attribute] = QueryTransformer::filterField($attribute).' = NULL';

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
    public function change(AttributeMapInterface $map, array $diff, array $object, EndpointObjectInterface $endpoint_object, bool $simulate = false): ?string
    {
        $values = array_values(array_intersect_key($object, $diff));
        list($filter, $fv) = $endpoint_object->getFilter();
        $values = array_merge($values, $fv);

        $this->logChange($filter, $diff);
        $query = 'UPDATE '.$this->table.' SET '.implode(',', $diff).' WHERE '.$filter;

        if ($simulate === false) {
            $this->socket->prepareValues($query, $values);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(AttributeMapInterface $map, array $object, EndpointObjectInterface $endpoint_object, bool $simulate = false): bool
    {
        list($filter, $values) = $endpoint_object->getFilter();
        $this->logDelete($filter);

        $sql = 'DELETE FROM '.$this->table.' WHERE '.$filter;

        if ($simulate === false) {
            $this->socket->prepareValues($sql, $values);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function transformQuery(?array $query = null)
    {
        if ($this->filter_all !== null && empty($query)) {
            return QueryTransformer::transform($this->getFilterAll());
        }
        if (!empty($query)) {
            if ($this->filter_all === null) {
                return QueryTransformer::transform($query);
            }

            return QueryTransformer::transform([
                    '$and' => [
                        $this->getFilterAll(),
                        $query,
                    ],
                ]);
        }

        return [null, []];
    }

    /**
     * Prepare.
     */
    protected function prepareCreate(array $object, bool $simulate = false)
    {
        $columns = [];
        $values = [];

        foreach ($object as $column => $value) {
            if (is_array($value)) {
                throw new Exception\EndpointCanNotHandleArray('endpoint can not handle array as a value ["'.$column.'"]. did you forget to set a decorator?');
            }

            $columns[] = QueryTransformer::filterField($column);
            $values[] = $value;
        }

        $sql = 'INSERT INTO '.$this->table.' ('.implode(',', $columns).') VALUES ('.implode(',', array_fill(0, count($values), '?')).')';

        if ($simulate === false) {
            return $this->socket->prepareValues($sql, $values);
        }

        return null;
    }
}
