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
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\Pdo\QueryTransformer;
use Tubee\Endpoint\SqlSrvUsers\Exception\InvalidQuery;
use Tubee\Endpoint\SqlSrvUsers\Exception\NoUsername;
use Tubee\Endpoint\SqlSrvUsers\Wrapper as SqlSrvWrapper;
use Tubee\EndpointObject\EndpointObjectInterface;
use Tubee\Workflow\Factory as WorkflowFactory;

class SqlSrvUsers extends AbstractEndpoint
{
    use LoggerTrait;

    /**
     * Kind.
     */
    public const KIND = 'SqlSrvUsersEndpoint';

    /**
     * LoginTable.
     */
    public const LOGINTABLE = 'master.sys.server_principals';

    /**
     * DefaultDatabase.
     */
    public const DEFAULTDATABASE = 'master';

    /**
     * DefaultLanguage.
     */
    public const DEFAULTLANGUAGE = 'us_english';

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
     * Disabled.
     */
    public const ATTRDISABLED = 'disabled';

    /**
     * PrincipalType.
     */
    public const ATTRPRINCIPALTYPE = 'typeDesc';

    /**
     * Database.
     */
    public const ATTRDATABASE = 'defaultDatabase';

    /**
     * Language.
     */
    public const ATTRLANGUAGE = 'defaultLanguage';

    /**
     * UserQuery.
     */
    public const USERQUERY =
        'SELECT * FROM ('
        .' SELECT loginData.principal_id, loginData.type_desc AS '.self::ATTRPRINCIPALTYPE.', loginData.name as '.self::ATTRLOGINNAME.','
        .'loginData.is_disabled as '.self::ATTRDISABLED.', userData.name as '.self::ATTRSQLNAME.','
        .' STRING_AGG(roles.name,\', \') AS '.self::ATTRUSERROLES.', loginData.default_database_name as '.self::ATTRDATABASE
        .', loginData.default_language_name AS '.self::ATTRLANGUAGE
        .' FROM '.self::LOGINTABLE.' as loginData'
        .' LEFT JOIN sys.database_principals as userData ON loginData.sid = userData.sid'
        .' LEFT JOIN sys.database_role_members as memberRole ON userData.principal_id = memberRole.member_principal_id'
        .' LEFT JOIN sys.database_principals as roles ON roles.principal_id = memberRole.role_principal_id'
        .' GROUP BY loginData.principal_id, loginData.type_desc, loginData.name, loginData.is_disabled, userData.name,'
        .'loginData.default_database_name, loginData.default_language_name'
        .') AS data';

    /**
     * UpdateAttributes with priority (key).
     */
    public const UPDATEATTRIBUTES = [
        self::ATTRLOGINNAME,
        self::ATTRSQLNAME,
        self::ATTRUSERROLES,
        self::ATTRDISABLED,
        self::ATTRDATABASE,
        self::ATTRLANGUAGE,
    ];

    /**
     * Socket.
     */
    protected $socket;

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
        list($filter, $values) = $this->transformQuery($query);

        if ($filter === null) {
            $sql = 'SELECT COUNT(*) as count FROM ('.self::USERQUERY.')';
        } else {
            $sql = 'SELECT COUNT(*) as count FROM ('.self::USERQUERY.' WHERE '.$filter.') AS count';
        }

        try {
            $result = $this->socket->prepareValues($sql, $values);

            return (int) $this->socket->getQueryResult($result)[0]['count'];
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
        list($filter, $values) = $query = $this->transformQuery($this->getFilterOne($object));
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
        $login_name = $this->getNameByAttribute($object, self::ATTRLOGINNAME);

        try {
            $this->socket->beginTransaction();
            if (isset($object['mechanism']) && $object['mechanism'] === 'windows') {
                $query = $this->createWindowsLogin($login_name);
            } else {
                if (!isset($object['password']) || $object['password'] === '') {
                    throw new Exception\AttributeNotResolvable('attribute password not found in object');
                }

                $query = $this->createLocalLogin($login_name, $object['password'], $object[self::ATTRHASTOCHANGEPWD] ?? true);
            }

            $this->socket->query($query, $simulate);

            if (isset($object['disabled']) && (bool) $object['disabled'] === true) {
                $this->disableLogin($login_name, $simulate);
            }

            if (isset($object[self::ATTRDATABASE])) {
                $this->setDatabase($login_name, $simulate, $object[self::ATTRDATABASE] ?? null);
            }

            if (isset($object[self::ATTRLANGUAGE])) {
                $this->setLanguage($login_name, $simulate, $object[self::ATTRLANGUAGE] ?? null);
            }
        } catch (InvalidQuery $e) {
            $this->logger->error('failed to create new login user', [
                'class' => get_class($this),
                'exception' => $e,
            ]);

            return null;
        }

        if (isset($object[self::ATTRSQLNAME]) && $object[self::ATTRSQLNAME] !== '') {
            try {
                $sql_name = $this->getNameByAttribute($object, self::ATTRSQLNAME);
                $this->socket->query($this->createSqlUserQuery($sql_name, $login_name), $simulate);

                if (isset($object[self::ATTRUSERROLES]) && $object[self::ATTRUSERROLES] !== []) {
                    $this->addRoles($sql_name, $object[self::ATTRUSERROLES], $simulate);
                }
            } catch (InvalidQuery $e) {
                $this->logger->error('failed to create new sql user', [
                    'class' => get_class($this),
                    'exception' => $e,
                ]);

                return null;
            }
        } else {
            $this->logger->info('no sql-user has been created', [
                'category' => get_class($this),
            ]);
        }

        $this->socket->commit();

        return (string) $this->getPrincipalId($login_name);
    }

    public function delete(AttributeMapInterface $map, array $object, EndpointObjectInterface $endpoint_object, bool $simulate = false): bool
    {
        $sql_user_query = '';
        $endpoint_object = $endpoint_object->getData();
        $this->socket->beginTransaction();

        if (isset($endpoint_object[self::ATTRSQLNAME])) {
            $sql_user_query = $this->dropSqlUserQuery($endpoint_object[self::ATTRSQLNAME]);
        }

        $login_query = $this->dropLoginQuery($endpoint_object[self::ATTRLOGINNAME]);

        try {
            if ($sql_user_query !== '') {
                $this->socket->query($sql_user_query, $simulate);
            }
            $this->socket->query($login_query, $simulate);
            $this->socket->commit();

            return true;
        } catch (InvalidQuery $e) {
            $this->logger->error('failed to delete object with query', [
                'class' => get_class($this),
                'exception' => $e,
            ]);

            return false;
        }
    }

    public function getDiff(AttributeMapInterface $map, array $diff): array
    {
        $result = [];
        foreach (self::UPDATEATTRIBUTES as $attr) {
            if (array_key_exists($attr, $diff)) {
                $result[] = [
                    'attrib' => $attr,
                    'data' => $diff[$attr],
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
        $endpoint_object = $endpoint_object->getData();
        $login_name = $object[self::ATTRLOGINNAME] ?? null;
        $sql_name = $object[self::ATTRSQLNAME] ?? null;
        $this->socket->beginTransaction();

        foreach ($diff as $key => $attr) {
            switch ($attr['attrib']) {
                case self::ATTRSQLNAME:
                    $sql_name = $this->changeSqlUser($simulate, (int) $attr['data']['action'], $login_name, $endpoint_object[self::ATTRSQLNAME] ?? null, $attr['data']['value'] ?? null);

                    break;
                case self::ATTRLOGINNAME:
                    $this->changeLoginName($simulate, (int) $attr['data']['action'], $endpoint_object[self::ATTRLOGINNAME], $attr['data']['value'] ?? null, $sql_name);

                    break;
                case self::ATTRUSERROLES:
                    $this->changeUserRoles($simulate, $sql_name, (int) $attr['data']['action'], $attr['data']['value'] ?? [], $endpoint_object[self::ATTRUSERROLES] ?? []);

                    break;
                case self::ATTRDISABLED:
                    $value = (bool) $attr['data']['value'];

                    if ($value === true) {
                        $this->disableLogin($login_name, $simulate);
                    } elseif ($value === false) {
                        $this->enableLogin($login_name, $simulate);
                    }

                    break;
                case self::ATTRDATABASE:
                    $this->setDatabase($login_name, $simulate, $attr['data']['value'] ?? null);

                    break;
                case self::ATTRLANGUAGE:
                    $this->setLanguage($login_name, $simulate, $attr['data']['value'] ?? null);

                    break;
                default:
                    $this->logger->error('unknown attribute [{attr}]', [
                        'class' => get_class($this),
                        'attr' => $attr['attrib'],
                    ]);

                    break;
            }
        }

        $this->socket->commit();

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

    protected function changeSqlUser(bool $simulate, int $action, ?string $login_name, ?string $object_name = null, ?string $new_name = null): ?string
    {
        if ($login_name === null) {
            $this->logger->error('no [{attr}] given while changing sql user', [
                'class' => get_class($this),
                'attr' => self::ATTRLOGINNAME,
            ]);

            return $object_name;
        }

        switch ($action) {
            case AttributeMapInterface::ACTION_REPLACE:
                if ($new_name === null) {
                    throw new NoUsername('no attribute '.self::ATTRSQLNAME.' found in data object');
                }

                if ($object_name === null) {
                    $query = $this->createSqlUserQuery($new_name, $login_name);
                } else {
                    $query = $this->renameSqlUserQuery($object_name, $new_name);
                }

                break;
            case AttributeMapInterface::ACTION_REMOVE:
                if ($object_name === null) {
                    return null;
                }
                $query = $this->dropSqlUserQuery($object_name);

                break;
            default:
                throw new InvalidArgumentException('unknown diff action '.$action.' given');
        }

        try {
            $this->socket->query($query, $simulate);

            return $new_name;
        } catch (InvalidQuery $e) {
            $this->logger->error('failed to modify sql user with query [{attr}]', [
                'class' => get_class($this),
                'exception' => $e,
                'attr' => $query,
            ]);

            return $object_name;
        }
    }

    protected function changeUserRoles(bool $simulate, ?string $name, int $action, array $new_roles, array $object_roles): void
    {
        if ($name === null) {
            $this->logger->error('no sql name is given while changing user roles', [
                'class' => get_class($this),
            ]);

            return;
        }

        switch ($action) {
            case AttributeMapInterface::ACTION_REPLACE:
                $roles_to_add = [];

                foreach ($new_roles as $role) {
                    if (in_array($role, $object_roles)) {
                        $key = array_search($role, $object_roles);
                        unset($object_roles[$key]);

                        continue;
                    }

                    $roles_to_add[] = $role;
                }

                $this->addRoles($name, $roles_to_add, $simulate);
                $this->removeRoles($name, $object_roles, $simulate);

                break;
            case AttributeMapInterface::ACTION_REMOVE:
                $this->removeRoles($name, $object_roles, $simulate);

                break;
            default:
                throw new InvalidArgumentException('unknown diff action '.$action.' given');
        }
    }

    protected function changeLoginName(bool $simulate, int $action, string $object_name, ?string $new_name = null, ?string $sql_user = null): void
    {
        $drop_login = false;

        switch ($action) {
            case AttributeMapInterface::ACTION_REPLACE:
                if ($new_name === null) {
                    throw new NoUsername('no attribute '.self::ATTRLOGINNAME.' found in data object');
                }

                $query = $this->renameLoginQuery($object_name, $new_name);

                break;
            case AttributeMapInterface::ACTION_REMOVE:
                $query = $this->dropLoginQuery($object_name);
                $drop_login = true;

                break;
            default:
                throw new InvalidArgumentException('unknown diff action '.$action.' given');
        }

        try {
            if ($drop_login && $sql_user !== null) {
                $this->socket->query($this->dropSqlUserQuery($sql_user), $simulate);
            }
            $this->socket->query($query, $simulate);
        } catch (InvalidQuery $e) {
            $this->logger->error('failed to modify login with query [{attr}]', [
                'class' => get_class($this),
                'exception' => $e,
                'attr' => $query,
            ]);
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

        throw new NoUsername('no attribute '.$attribute.' found in data object');
    }

    /**
     * Create windows login query.
     */
    protected function createWindowsLogin(string $name): string
    {
        return 'CREATE LOGIN ['.htmlentities($name, ENT_QUOTES).'] FROM WINDOWS';
    }

    /**
     * Create local login query.
     */
    protected function createLocalLogin(string $name, string $password, bool $change_password): string
    {
        $query = 'CREATE LOGIN ['.htmlentities($name, ENT_QUOTES)."] WITH PASSWORD = '".htmlentities($password, ENT_QUOTES)."'";

        if ($change_password) {
            $query .= ' MUST_CHANGE, CHECK_EXPIRATION = ON';
        }

        return $query;
    }

    /**
     * Create sql user query.
     */
    protected function createSqlUserQuery(string $name, string $login_name): string
    {
        return 'CREATE USER ['.htmlentities($name, ENT_QUOTES).'] FOR LOGIN ['.htmlentities($login_name, ENT_QUOTES).']';
    }

    protected function renameSqlUserQuery(string $old_name, string $new_name): string
    {
        return 'ALTER USER ['.htmlentities($old_name, ENT_QUOTES).'] WITH NAME = ['.htmlentities($new_name, ENT_QUOTES).']';
    }

    protected function dropSqlUserQuery(string $name): string
    {
        return 'DROP USER ['.htmlentities($name, ENT_QUOTES).']';
    }

    /**
     * Add user to given role.
     */
    protected function addRoles(string $name, array $roles, bool $simulate): bool
    {
        foreach ($roles as $role) {
            try {
                $query = 'EXEC sp_addrolemember '.htmlentities($role, ENT_QUOTES).', ['.htmlentities($name, ENT_QUOTES).']';

                $this->socket->query($query, $simulate);
            } catch (InvalidQuery $e) {
                $this->logger->error('failed to add user role', [
                    'class' => get_class($this),
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
                    'class' => get_class($this),
                    'exception' => $e,
                ]);

                return false;
            }
        }

        return true;
    }

    protected function dropLoginQuery(string $name): string
    {
        return 'DROP LOGIN ['.htmlentities($name, ENT_QUOTES).']';
    }

    protected function renameLoginQuery(string $old_name, string $new_name): string
    {
        return 'ALTER LOGIN ['.htmlentities($old_name, ENT_QUOTES).'] WITH NAME = ['.htmlentities($new_name, ENT_QUOTES).']';
    }

    /**
     * Disable login.
     */
    protected function disableLogin(?string $name, bool $simulate): void
    {
        if ($name === null) {
            $this->logger->error('no login name is given while disabling login', [
                'class' => get_class($this),
            ]);
        }

        $this->socket->query('ALTER LOGIN ['.htmlentities($name, ENT_QUOTES).'] DISABLE', $simulate);
    }

    /**
     * Set database.
     */
    protected function setDatabase(?string $name, bool $simulate, ?string $database): void
    {
        if ($name === null) {
            $this->logger->error('no login name is given while set default database', [
                'class' => get_class($this),
            ]);
        }

        if ($database === null) {
            $database = self::DEFAULTDATABASE;
        }

        $this->socket->query('ALTER LOGIN ['.htmlentities($name, ENT_QUOTES).'] WITH DEFAULT_DATABASE = ['.htmlentities($database, ENT_QUOTES).']', $simulate);
    }

    /**
     * Set language.
     */
    protected function setLanguage(?string $name, bool $simulate, ?string $language): void
    {
        if ($name === null) {
            $this->logger->error('no login name is given while set default language', [
                'class' => get_class($this),
            ]);
        }

        if ($language === null) {
            $language = self::DEFAULTLANGUAGE;
        }

        $this->socket->query('ALTER LOGIN ['.htmlentities($name, ENT_QUOTES).'] WITH DEFAULT_LANGUAGE = ['.htmlentities($language, ENT_QUOTES).']', $simulate);
    }

    /**
     * Enable login.
     */
    protected function enableLogin(?string $name, bool $simulate): void
    {
        if ($name === null) {
            $this->logger->error('no login name is given while enabling login', [
                'class' => get_class($this),
            ]);
        }

        $this->socket->query('ALTER LOGIN ['.$name.'] ENABLE', $simulate);
    }

    protected function getPrincipalId(string $name): int
    {
        $query = 'SELECT principal_id FROM '.self::LOGINTABLE.' WHERE name = ?';
        $result = $this->socket->prepareValues($query, [htmlentities($name, ENT_QUOTES)]);
        $result = $this->socket->getQueryResult($result)[0];

        if (is_int($result['principal_id'])) {
            return $result['principal_id'];
        }

        throw new Exception\AttributeNotResolvable('could not resolve principal_id of object with name '.$name);
    }
}
