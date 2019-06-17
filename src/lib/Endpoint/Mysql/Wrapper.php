<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Mysql;

use mysqli;
use mysqli_result;
use mysqli_stmt;
use Psr\Log\LoggerInterface;

class Wrapper extends mysqli
{
    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Host.
     *
     * @var string
     */
    protected $host;

    /**
     * Username.
     *
     * @var string
     */
    protected $username;

    /**
     * Password.
     *
     * @var string
     */
    protected $passwd;

    /**
     * dbname.
     *
     * @var string
     */
    protected $dbname;

    /**
     * Socket.
     *
     * @var string
     */
    protected $socket;

    /**
     * Port.
     *
     * @var string
     */
    protected $port;

    /**
     * Options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Charset.
     *
     * @var string
     */
    protected $charset = 'utf8';

    /**
     * construct.
     */
    public function __construct(string $host, LoggerInterface $logger, ?string $username = null, ?string $passwd = null, ?string $dbname = null, ?int $port = 3306, ?string $socket = null, ?array $options = [])
    {
        $this->logger = $logger;
        $this->host = $host;
        $this->username = $username;
        $this->passwd = $passwd;
        $this->port = $port;
        $this->dbname = $dbname;
        $this->socket = $socket;
        $this->options = $options;
    }

    /**
     * Setup.
     */
    public function connect(): Wrapper
    {
        parent::__construct($this->host, $this->username, $this->passwd, $this->options);

        return $this;
    }

    /**
     * Query.
     */
    public function select(string $query): mysqli_result
    {
        $this->logger->debug('execute sql query ['.$query.']', [
            'category' => get_class($this),
        ]);

        $result = parent::query($query);

        if (false === $result) {
            throw new Exception\InvalidQuery('failed to execute sql query with error '.$this->error.' ('.$this->errno.')');
        }

        return $result;
    }

    /**
     * Select query.
     */
    public function query(string $query): bool
    {
        $this->logger->debug('execute sql query ['.$query.']', [
            'category' => get_class($this),
        ]);

        $result = parent::query($query);

        if (false === $result) {
            throw new Exception\InvalidQuery('failed to execute sql query with error '.$this->error.' ('.$this->errno.')');
        }

        return $result;
    }

    /**
     * Prepare query.
     */
    public function prepareValues(string $query, iterable $values): mysqli_stmt
    {
        $this->logger->debug('prepare and execute sql query ['.$query.'] with values [{values}]', [
            'category' => get_class($this),
            'values' => $values,
        ]);

        $stmt = $this->prepare($query);

        if (!($stmt instanceof mysqli_stmt)) {
            throw new Exception\InvalidQuery('failed to prepare sql query with error '.$this->error.' ('.$this->errno.')');
        }

        $types = '';
        foreach ($values as $attr => $value) {
            $types .= 's';
        }

        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        if ($stmt->error) {
            throw new Exception\InvalidQuery($stmt->error);
        }

        return $stmt;
    }
}
