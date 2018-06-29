<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Pdo;

use PDO;
use PDOStatement;
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
     * Connection resource.
     *
     * @var PDO
     */
    protected $pdo;

    /**
     * construct.
     */
    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->pdo = $pdo;
    }

    /**
     * Forward calls.
     *
     * @param array $method
     */
    public function __call(string $method, array $arguments = [])
    {
        return call_user_func_array([&$this->pdo, $method], $arguments);
    }

    /**
     * Get connection.
     */
    public function getResource(): PDO
    {
        return $this->pdo;
    }

    /**
     * Query.
     */
    public function select(string $query): PDOStatement
    {
        $this->logger->debug('execute sql query ['.$query.']', [
            'category' => get_class($this),
        ]);

        $link = $this->getResource();
        $result = $link->query($query);

        if (false === $result) {
            throw new Exception\InvalidQuery('failed to execute sql query with error '.$link->errorInfo()[2].' ('.$link->errorCode().')');
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

        $link = $this->getResource();
        $result = $link->exec($query);

        if (false === $result) {
            throw new Exception\InvalidQuery('failed to execute sql query with error '.$link->errorInfo().' ('.$link->errorCode().')');
        }
        $this->logger->debug('sql query affected ['.$result.'] rows', [
                'category' => get_class($this),
            ]);

        return true;
    }

    /**
     * Prepare query.
     */
    public function prepare(string $query, Iterable $values): PDOStatement
    {
        $this->logger->debug('prepare and execute pdo query ['.$query.'] with values [{values}]', [
            'category' => get_class($this),
            'values' => $values,
        ]);

        $link = $this->getResource();
        $stmt = $link->prepare($query);

        if (!($stmt instanceof PDOStatement)) {
            throw new Exception\InvalidQuery('failed to prepare pdo query with error '.$link->error.' ('.$link->errno.')');
        }

        $types = '';
        foreach ($values as $attr => $value) {
            $types .= 's';
        }

        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        return $stmt;
    }
}
