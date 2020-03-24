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
use Tubee\Endpoint\SqlSrvUsers\Exception\InvalidQuery;
use Tubee\Endpoint\SqlSrvUsers\Exception\NoUsername;
use Tubee\Endpoint\Pdo\QueryTransformer;
use Tubee\Endpoint\SqlSrvUsers\Wrapper as SqlSrvWrapper;
use Tubee\Collection\CollectionInterface;
use Tubee\EndpointObject\EndpointObjectInterface;
use Tubee\Workflow\Factory as WorkflowFactory;

class SqlSrvUsers extends AbstractEndpoint
{
    use LoggerTrait;

    /**
     * Socket.
     */
    protected $socket;

    /**
     * Kind.
     */
    public const KIND = 'SqlSrvUsersEndpoint';

    /**
     * LoginTable.
     */
    public const LOGINTABLE = 'master.sys.sql_logins';

    /**
     * LoginName.
     */
    public const ATTRIBUTELOGINNAME = 'loginName';

    /**
     * SqlName.
     */
    public const ATTRIBUTESQLNAME = 'sqlName';

    /**
     * UserQuery.
     */
    public const USERQUERY =
        'SELECT * FROM ('
        .' SELECT loginData.principal_id, loginData.name as loginName, loginData.is_disabled as disabled, userData.name as sqlName, roles.name as RoleName'
        .' FROM master.sys.sql_logins as loginData'
        .' LEFT JOIN sys.database_principals as userData ON loginData.sid = userData.sid'
        .' LEFT JOIN sys.database_role_members as memberRole ON userData.principal_id = memberRole.member_principal_id'
        .' LEFT JOIN sys.database_principals as roles ON roles.principal_id = memberRole.role_principal_id'
        .') AS data';

    /**
     *
     */
    public const UPDATEATTRIBUTES = [
        'loginName',
        'sqlName',
        'createSqlUser',
        'hasToChangePwd',
        'userRoles',
        'password',
        'disabled'
    ];

    /**
     * Init endpoint.
     */
    public function __construct(string $name, string $type, SqlSrvWrapper $socket, CollectionInterface $collection, WorkflowFactory $workflow, LoggerInterface $logger, array $resource = [])
    {
        $this->socket = $socket;
        parent::__construct($name, $type, $collection, $workflow, $logger, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function setup(bool $simulate = false): EndpointInterface
    {
        $this->socket->initialize();

        return $this;
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
        [$filter, $values] = $this->transformQuery($query);

        if ($filter === null) {
            $sql = 'SELECT COUNT(*) as count FROM '.self::LOGINTABLE;
        } else {
            $sql = 'SELECT COUNT(*) as count FROM '.self::LOGINTABLE.' WHERE '.$filter;
        }

        try {
            $result = $this->socket->prepareValues($sql, $values);

            return (int) $this->socket->getQueryResult($result)['count'];
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
        [$filter, $values] = $this->transformQuery($query);
        $this->logGetAll($filter);

        if ($filter === null) {
            $sql = self::USERQUERY;
        } else {
            $sql = self::USERQUERY.' WHERE '.$filter;
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
        $result = $this->socket->getQueryResult($result);

        foreach ($result as $object) {
            yield $this->build($this->prepareRawObject($object));
            ++$i;
        }

        return $i;
    }

    /**
     * {@inheritdoc}
     */
    public function getOne(array $object, array $attributes = []): EndpointObjectInterface
    {
        [$filter, $values] = $query = $this->transformQuery($this->getFilterOne($object));
        $this->logGetOne($filter);

        $sql = self::USERQUERY.' WHERE '.$filter;
        $result = $this->socket->prepareValues($sql, $values);
        $result = $this->socket->getQueryResult($result);

        if (count($result) > 1) {
            throw new Exception\ObjectMultipleFound('found more than one object with filter '.$filter);
        }
        $return = array_shift($result);
        if ($return === null) {
            throw new Exception\ObjectNotFound('no object found with filter '.$filter);
        }

        return $this->build($this->prepareRawObject($return), $query);
    }

    /**
     * {@inheritdoc}
     */
    public function create(AttributeMapInterface $map, array $object, bool $simulate = false): ?string
    {
        $this->logCreate($object);
        $loginName = htmlentities($this->getNameByAttribute($object, self::ATTRIBUTELOGINNAME), ENT_QUOTES);

        if ($simulate === true) {
            return null;
        }

        try {
            if (isset($object['mechanism']) && $object['mechanism'] === 'windows') {
                $query = $this->createWindowsLogin($loginName, $object['domain'] ?? '');
            } else {
                $password = htmlentities($object['password'], ENT_QUOTES);
                $query = $this->createLocalLogin($loginName, $password, $object['hasToChangePwd'] ?? true);
            }

            $this->socket->query($query);

            if (isset($object['disabled']) && $object['disabled'] === true) {
                $this->disableLogin($loginName);
            }

            $principalId = $this->getPrincipalId($loginName);
        } catch (InvalidQuery $e) {
            $this->logger->error('failed to create new login user', [
                'class'     => get_class($this),
                'exception' => $e,
            ]);

            return null;
        }

        if (isset($object['createSqlUser']) && $object['createSqlUser'] !== false) {
            try {
                $sqlName = htmlentities($this->getNameByAttribute($object, self::ATTRIBUTESQLNAME), ENT_QUOTES);
                $this->socket->query($this->createSqlUserQuery($sqlName, $loginName));
            } catch (InvalidQuery $e) {
                $this->logger->error('failed to create new sql user', [
                    'class'     => get_class($this),
                    'exception' => $e,
                ]);

                return null;
            }
        } else {
            $this->logger->info('no sql-user has been created', [
                'category' => get_class($this),
            ]);
        }

        if (isset($object['userRoles']) && $object['userRoles'] !== []) {
            $this->addRole($sqlName, $object['userRoles']);
        }

        return (string)$principalId;
    }

    public function delete(AttributeMapInterface $map, array $object, EndpointObjectInterface $endpoint_object, bool $simulate = false): bool
    {
        throw new Exception\UnsupportedEndpointOperation('endpoint '.get_class($this).' does not support delete() yet');
    }

    public function getDiff(AttributeMapInterface $map, array $diff): array
    {
        $result = [];
        foreach ($diff as $attribute => $update) {
            if (in_array($attribute, self::UPDATEATTRIBUTES)) {
                $result[] = [
                    'attrib' => $attribute,
                    'values' => $update
                ];
            }
        }

        return $result;
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

    public function change(AttributeMapInterface $map, array $diff, array $object, EndpointObjectInterface $endpoint_object, bool $simulate = false): ?string
    {
        throw new Exception\UnsupportedEndpointOperation('endpoint '.get_class($this).' does not support change() yet');
    }

    /**
     * Prepare object.
     */
    protected function prepareRawObject(array $result): array
    {
        $object = [];
        foreach ($result as $key => $attr) {
            if ($key === 'count') {
                continue;
            }

            if (!is_int($key)) {
                if (json_encode($attr) === false) {
                    $object[$key] = base64_encode($attr);
                } else {
                    $object[$key] = $attr;
                }
            }
        }

        return $object;
    }

    /**
     * Get username.
     */
    protected function getNameByAttribute(array $record, string $attribute): string
    {
        if (isset($record[$attribute])) {
            return $record[$attribute];
        }

        throw new NoUsername('no attribute '.$attribute.' found in data object');
    }

    /**
     * Create windows login query.
     */
    protected function createWindowsLogin(string $name, string $domain): string
    {
        return "CREATE LOGIN [".$domain."\\".$name."] FROM WINDOWS";
    }

    /**
     * Create local login query.
     */
    protected function createLocalLogin(string $name, string $password, bool $changePassword): string
    {
        $query = "CREATE LOGIN [".$name."] WITH PASSWORD = '".$password."'";

        if ($changePassword) {
            $query .= ' MUST_CHANGE, CHECK_EXPIRATION = ON';
        }

        return $query;
    }

    /**
     * Create sql user query.
     */
    protected function createSqlUserQuery(string $name, string $loginName): string
    {
        return 'CREATE USER ['.$name.'] FOR LOGIN ['.$loginName.']';
    }

    /**
     * Add user to given role.
     */
    protected function addRole(string $name, array $roles): bool {
        foreach ($roles as $role) {
            try {
                $query = 'EXEC sp_addrolemember '.$role.', ['.$name.']';

                $this->socket->query($query);
            } catch (InvalidQuery $e) {
                $this->logger->error('failed to add user role', [
                    'class'     => get_class($this),
                    'exception' => $e,
                ]);

                return false;
            }
        }

        return true;
    }

    /**
     * Disable login.
     */
    protected function disableLogin(string $name): void
    {
        $this->socket->query('ALTER LOGIN ['.$name.'] DISABLE');
    }

    /**
     * Enable login.
     */
    protected function enableLogin(string $name): void
    {
        $this->socket->query('ALTER LOGIN ['.$name.'] ENABLE');
    }

    protected function getPrincipalId(string $name)
    {
        $query = 'SELECT principal_id FROM '.self::LOGINTABLE.' WHERE name = ?';
        $result = $this->socket->prepareValues($query, [$name]);
        $result = $this->socket->getQueryResult($result)[0];

        return $result['principal_id'];
    }
}
