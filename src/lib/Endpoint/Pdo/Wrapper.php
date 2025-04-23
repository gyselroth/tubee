<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Pdo;

use PDO;
use PDOStatement;
use Psr\Log\LoggerInterface;

class Wrapper extends PDO
{
    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * DSN.
     *
     * @var string
     */
    protected $dsn;

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
    protected $password;

    /**
     * Options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * construct.
     */
    public function __construct(string $dsn, LoggerInterface $logger, ?string $username = null, ?string $password = null, ?array $options = null)
    {
        $this->logger = $logger;
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options = $options;
    }

    /**
     * Connect.
     */
    public function initialize(): Wrapper
    {
        parent::__construct($this->dsn, $this->username, $this->password, $this->options);

        return $this;
    }

    /**
     * Select query.
     */
    public function query(string $query): bool
    {
        $this->logger->debug('execute sql query ['.$query.']', [
            'category' => get_class($this),
        ]);

        $result = $this->exec($query);

        if (false === $result) {
            throw new Exception\InvalidQuery('failed to execute sql query with error '.implode(',', $this->errorInfo()).' ('.$this->errorCode().')');
        }
        $this->logger->debug('sql query affected ['.$result.'] rows', [
                'category' => get_class($this),
            ]);

        return true;
    }

    /**
     * Prepare query.
     */
    public function prepareValues(string $query, array $values): PDOStatement
    {
        $this->logger->debug('prepare and execute pdo query ['.$query.'] with values [{values}]', [
            'category' => get_class($this),
            'values' => $values,
        ]);

        $stmt = $this->prepare($query);

        if (!($stmt instanceof PDOStatement)) {
            throw new Exception\InvalidQuery('failed to prepare pdo query with error '.implode(',', $this->errorInfo()).' ('.$this->errorCode().')');
        }

        $result = $stmt->execute($values);

        if ($result === false) {
            throw new Exception\InvalidQuery('failed to execute prepared pdo query with error '.implode(',', $this->errorInfo()).' ('.$this->errorCode().')');
        }

        return $stmt;
    }
}
