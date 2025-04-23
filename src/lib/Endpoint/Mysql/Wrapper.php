<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Mysql;

use mysqli;
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
     * @var int
     */
    protected $port = 3306;

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
    public function __construct(string $host, LoggerInterface $logger, string $dbname, ?string $username = null, ?string $passwd = null, ?int $port = 3306, ?string $socket = null, ?array $options = [])
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
    public function initialize(): Wrapper
    {
        parent::__construct($this->host, $this->username ?? '', $this->passwd ?? '', $this->dbname, $this->port ?? 3306);

        return $this;
    }

    /**
     * Select query.
     */
    public function query($query, $resultmode = null)
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
    public function prepareValues(string $query, array $values): mysqli_stmt
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
            switch (gettype($value)) {
            case 'integer':
                $types .= 'i';

            break;
            case 'double':
                $types .= 'd';

            break;
            default:
            case 'string':
                $types .= 's';
            }
        }

        if (count($values) > 0) {
            $stmt->bind_param($types, ...array_values($values));
        }

        $stmt->execute();

        if ($stmt->error) {
            throw new Exception\InvalidQuery($stmt->error);
        }

        return $stmt;
    }
}
