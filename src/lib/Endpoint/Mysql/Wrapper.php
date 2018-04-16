<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Mysql;

use mysqli;
use mysqli_result;
use mysqli_stmt;
use Psr\Log\LoggerInterface;

class Wrapper
{
    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Charset.
     *
     * @var string
     */
    protected $charset = 'utf8';

    /**
     * Mysql connection.
     *
     * @var mysqli
     */
    protected $mysqli;

    /**
     * construct.
     *
     * @param mysqli          $mysqli
     * @param LoggerInterface $logger
     */
    public function __construct(mysqli $mysqli, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->mysqli = $mysqli;
    }

    /**
     * Forward calls.
     *
     * @param array $method
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call(string $method, array $arguments = [])
    {
        return call_user_func_array([&$this->mysqli, $method], $arguments);
    }

    /**
     * Get connection.
     *
     * @return resource
     */
    public function getResource(): mysqli
    {
        return $this->mysqli;
    }

    /**
     * Query.
     *
     * @param string $query
     *
     * @return mysqli_result
     */
    public function select(string $query): mysqli_result
    {
        $this->logger->debug('execute sql query ['.$query.']', [
            'category' => get_class($this),
        ]);

        $link = $this->getResource();
        $result = $link->query($query);

        if (false === $result) {
            throw new Exception\InvalidQuery('failed to execute sql query with error '.$link->error.' ('.$link->errno.')');
        }

        return $result;
    }

    /**
     * Select query.
     *
     * @param string $query
     *
     * @return bool
     */
    public function query(string $query): bool
    {
        $this->logger->debug('execute sql query ['.$query.']', [
            'category' => get_class($this),
        ]);

        $link = $this->getResource();
        $result = $link->query($query);

        if (false === $result) {
            throw new Exception\InvalidQuery('failed to execute sql query with error '.$link->error.' ('.$link->errno.')');
        }

        return $result;
    }

    /**
     * Prepare query.
     *
     * @param string   $query
     * @param iterable $values
     *
     * @return mysqli_stmt
     */
    public function prepare(string $query, Iterable $values): mysqli_stmt
    {
        $this->logger->debug('prepare and execute sql query ['.$query.'] with values [{values}]', [
            'category' => get_class($this),
            'values' => $values,
        ]);

        $link = $this->getResource();
        $stmt = $link->prepare($query);

        if (!($stmt instanceof mysqli_stmt)) {
            throw new Exception\InvalidQuery('failed to prepare sql query with error '.$link->error.' ('.$link->errno.')');
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
