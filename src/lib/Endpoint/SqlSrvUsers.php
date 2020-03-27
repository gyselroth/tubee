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
use InvalidArgumentException;
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
    public const ATTRLOGINNAME = 'loginName';

    /**
     * SqlName.
     */
    public const ATTRSQLNAME = 'sqlName';

    /**
     * HasToChangePassword.
     */
    public const ATTRHASTOCHANGEPWD = 'hasToChangePwd';

    /**
     * UserRoles.
     */
    public const ATTRUSERROLES = 'userRoles';

    /**
     * Password.
     */
    public const ATTRPWD = 'password';

    /**
     * Disabled.
     */
    public const ATTRDISABLED = 'disabled';

    /**
     * UserQuery.
     */
    public const USERQUERY =
        'SELECT * FROM ('
        . ' SELECT loginData.principal_id, loginData.name as loginName, loginData.is_disabled as disabled, userData.name as sqlName,'
        . ' STRING_AGG(roles.name,\', \') AS userRoles'
        . ' FROM master.sys.sql_logins as loginData'
        . ' LEFT JOIN sys.database_principals as userData ON loginData.sid = userData.sid'
        . ' LEFT JOIN sys.database_role_members as memberRole ON userData.principal_id = memberRole.member_principal_id'
        . ' LEFT JOIN sys.database_principals as roles ON roles.principal_id = memberRole.role_principal_id'
        . ' GROUP BY loginData.principal_id, loginData.name, loginData.is_disabled, userData.name'
        . ') AS data';

    /**
     * UpdateAttributes with priority (key).
     */
    public const UPDATEATTRIBUTES = [
        self::ATTRLOGINNAME,
        self::ATTRSQLNAME,
        self::ATTRUSERROLES,
        self::ATTRPWD,
        self::ATTRDISABLED
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
            $sql = 'SELECT COUNT(*) as count FROM ' . self::LOGINTABLE;
        } else {
            $sql = 'SELECT COUNT(*) as count FROM ' . self::LOGINTABLE . ' WHERE ' . $filter;
        }

        try {
            $result = $this->socket->prepareValues($sql, $values);

            return (int)$this->socket->getQueryResult($result)['count'];
        } catch (InvalidQuery $e) {
            $this->logger->error('failed to count number of objects from endpoint', [
                'class'     => get_class($this),
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
            $sql = self::USERQUERY . ' WHERE ' . $filter;
        }

        try {
            $result = $this->socket->prepareValues($sql, $values);
        } catch (InvalidQuery $e) {
            $this->logger->error('failed to fetch resources from endpoint', [
                'class'     => get_class($this),
                'exception' => $e,
            ]);

            return 0;
        }

        $i      = 0;
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

        $sql    = self::USERQUERY . ' WHERE ' . $filter;
        $result = $this->socket->prepareValues($sql, $values);
        $result = $this->socket->getQueryResult($result);

        if (count($result) > 1) {
            throw new Exception\ObjectMultipleFound('found more than one object with filter ' . $filter);
        }
        $return = array_shift($result);
        if ($return === null) {
            throw new Exception\ObjectNotFound('no object found with filter ' . $filter);
        }

        return $this->build($this->prepareRawObject($return), $query);
    }

    /**
     * {@inheritdoc}
     */
    public function create(AttributeMapInterface $map, array $object, bool $simulate = false): ?string
    {
        $this->logCreate($object);
        $loginName = $this->getNameByAttribute($object, self::ATTRLOGINNAME);

        if ($simulate === true) {
            return null;
        }

        try {
            if (isset($object['mechanism']) && $object['mechanism'] === 'windows') {
                $query = $this->createWindowsLogin($loginName, $object['domain'] ?? '');
            } else {
                $query = $this->createLocalLogin($loginName, $object['password'], $object[self::ATTRHASTOCHANGEPWD] ?? true);
            }

            $this->socket->query($query, $simulate);

            if (isset($object['disabled']) && $object['disabled'] === true) {
                $this->disableLogin($loginName, $simulate);
            }

            $principalId = $this->getPrincipalId($loginName);
        } catch (InvalidQuery $e) {
            $this->logger->error('failed to create new login user', [
                'class'     => get_class($this),
                'exception' => $e,
            ]);

            return null;
        }

        if (isset($object[self::ATTRSQLNAME]) && $object[self::ATTRSQLNAME] !== '') {
            try {
                $sqlName = $this->getNameByAttribute($object, self::ATTRSQLNAME);
                $this->socket->query($this->createSqlUserQuery($sqlName, $loginName), $simulate);

                if (isset($object[self::ATTRUSERROLES]) && $object[self::ATTRUSERROLES] !== []) {
                    $this->addRoles($sqlName, $object[self::ATTRUSERROLES], $simulate);
                }
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

        return (string)$principalId;
    }

    public function delete(AttributeMapInterface $map, array $object, EndpointObjectInterface $endpoint_object, bool $simulate = false): bool
    {
        throw new Exception\UnsupportedEndpointOperation('endpoint ' . get_class($this) . ' does not support delete() yet');
    }

    public function getDiff(AttributeMapInterface $map, array $diff): array
    {
        $result = [];
        foreach (self::UPDATEATTRIBUTES as $attr) {
            if (array_key_exists($attr, $diff)) {
                $result[] = [
                    'attrib' => $attr,
                    'data'   => $diff[$attr]
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
//        self::ATTRLOGINNAME,
//        self::ATTRUSERROLES,
//        self::ATTRPWD,
//        self::ATTRDISABLED

        $endpoint_object = $endpoint_object->getData();
        $loginName       = $object['loginName'];
        $sqlName         = $object['sqlName'];

        foreach ($diff as $key => $attr) {
            switch ($attr['attrib']) {
                case self::ATTRSQLNAME:
                    $sqlName = $this->sqlUser($simulate, (int)$attr['data']['action'], $loginName, $attr['data']['value'] ?? null, $endpoint_object[self::ATTRSQLNAME]);
                    break;
                case self::ATTRLOGINNAME:
                    // $loginName = 'newLoginName';
                    break;
                case self::ATTRUSERROLES:
                    $this->userRoles($simulate, $sqlName, (int)$attr['data']['action'], $attr['data']['value'] ?? [], $endpoint_object[self::ATTRUSERROLES] ?? []);
                    break;
                default:
                    $this->logger->error('abc' . print_r($attr['attrib'], true), [
                        'class' => get_class($this)
                    ]);
                    break;
            }
        }


        throw new Exception\UnsupportedEndpointOperation('endpoint ' . get_class($this) . ' does not support change() yet');
//        $values = array_values(array_intersect_key($object, $diff));
//        list($filter, $fv) = $endpoint_object->getFilter();
//        $values = array_merge($values, $fv);
//
//        $this->logChange($filter, $diff);
//        $query = 'UPDATE '.$this->table.' SET '.implode(',', $diff).' WHERE '.$filter;
//
//        if ($simulate === false) {
//            $this->socket->prepareValues($query, $values);
//        }

        return null;
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
                if ($key === self::ATTRUSERROLES && $attr !== null) {
                    if (strpos($attr, ', ') !== false) {
                        $object[$key] = explode(', ', $attr);
                    } else {
                        $object[$key] = [$attr];
                    }
                } elseif (json_encode($attr) === false) {
                    $object[$key] = base64_encode($attr);
                } else {
                    $object[$key] = $attr;
                }
            }
        }

        return $object;
    }

    protected function sqlUser(bool $simulate, int $action, string $loginName, string $newName = null, string $objectName = null): string
    {
        switch ($action) {
            case AttributeMapInterface::ACTION_REPLACE:
                if ($newName === null) {
                    throw new NoUsername('no attribute ' . self::ATTRLOGINNAME . ' found in data object');
                }

                if ($objectName === null) {
                    $query = $this->createSqlUserQuery($newName, $loginName);
                } elseif ($objectName !== $newName) {
                    $query = $this->updateSqlUserQuery($objectName, $newName);
                } else {
                    $this->logger->info('sql user is already up2date', [
                        'class' => get_class($this),
                    ]);

                    return $newName;
                }
                break;
            case AttributeMapInterface::ACTION_REMOVE:
                if ($objectName === null) {
                    return '';
                }
                $query = $this->dropSqlUserQuery($objectName);
                break;
            default:
                throw new InvalidArgumentException('unknown diff action ' . $action . ' given');
        }

        try {
            $this->socket->query($query, $simulate);
            return $newName;
        } catch (InvalidQuery $e) {
            $this->logger->error('failed to modify sql user with query ' . $query, [
                'class'     => get_class($this),
                'exception' => $e,
            ]);

            return $newName;
        }
    }

    protected function userRoles(bool $simulate, string $name, int $action, array $newRoles, array $objectRoles): void
    {
        switch ($action) {
            case AttributeMapInterface::ACTION_REPLACE:
                $rolesToAdd = [];

                foreach ($newRoles as $role) {
                    if (in_array($role, $objectRoles)) {
                        $key = array_search($role, $objectRoles);
                        unset($objectRoles[$key]);
                        continue;
                    }

                    $rolesToAdd[] = $role;
                }

                $this->addRoles($name, $rolesToAdd, $simulate);
                $this->removeRoles($name, $objectRoles, $simulate);

                break;
            case AttributeMapInterface::ACTION_REMOVE:
                $this->removeRoles($name, $objectRoles, $simulate);

                break;
            default:
                throw new InvalidArgumentException('unknown diff action ' . $action . ' given');
        }
    }

    /**
     * Get username.
     */
    protected function getNameByAttribute(array $record, string $attribute): string
    {
        if (isset($record[$attribute])) {
            return $record[$attribute];
        }

        throw new NoUsername('no attribute ' . $attribute . ' found in data object');
    }

    /**
     * Create windows login query.
     */
    protected function createWindowsLogin(string $name, string $domain): string
    {
        return "CREATE LOGIN [" . htmlentities($domain, ENT_QUOTES) . "\\" . htmlentities($name, ENT_QUOTES) . "] FROM WINDOWS";
    }

    /**
     * Create local login query.
     */
    protected function createLocalLogin(string $name, string $password, bool $changePassword): string
    {
        $query = "CREATE LOGIN [" . htmlentities($name, ENT_QUOTES) . "] WITH PASSWORD = '" . htmlentities($password, ENT_QUOTES) . "'";

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
        return 'CREATE USER ['.htmlentities($name, ENT_QUOTES).'] FOR LOGIN [' . htmlentities($loginName, ENT_QUOTES) . ']';
    }

    protected function updateSqlUserQuery(string $oldName, string $newName): string
    {
        return 'ALTER USER ['.htmlentities($oldName, ENT_QUOTES).'] WITH NAME = [' . htmlentities($newName, ENT_QUOTES) . ']';
    }

    protected function dropSqlUserQuery(string $name): string
    {
        return 'DROP USER [' .htmlentities($name, ENT_QUOTES).']';
    }

    /**
     * Add user to given role.
     */
    protected function addRoles(string $name, array $roles, bool $simulate): bool {
        foreach ($roles as $role) {
            try {
                $query = 'EXEC sp_addrolemember '.htmlentities($role, ENT_QUOTES).', ['.htmlentities($name, ENT_QUOTES).']';

                $this->socket->query($query, $simulate);
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

    protected function removeRoles(string $name, array $roles, bool $simulate): bool
    {
        foreach ($roles as $role) {
            try {
                if ($role === '') {
                    continue;
                }

                $query = 'EXEC sp_droprolemember '.htmlentities($role, ENT_QUOTES).', ['.htmlentities($name, ENT_QUOTES).']';

                $this->socket->query($query, $simulate);
            } catch (InvalidQuery $e) {
                $this->logger->error('failed to remove user role', [
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
    protected function disableLogin(string $name, bool $simulate): void
    {
        $this->socket->query('ALTER LOGIN ['.htmlentities($name, ENT_QUOTES).'] DISABLE', $simulate);
    }

    /**
     * Enable login.
     */
    protected function enableLogin(string $name, bool $simulate): void
    {
        $this->socket->query('ALTER LOGIN ['.$name.'] ENABLE', $simulate);
    }

    protected function getPrincipalId(string $name)
    {
        $query = 'SELECT principal_id FROM '.self::LOGINTABLE.' WHERE name = ?';
        $result = $this->socket->prepareValues($query, [htmlentities($name, ENT_QUOTES)]);
        $result = $this->socket->getQueryResult($result)[0];

        return $result['principal_id'];
    }
}
