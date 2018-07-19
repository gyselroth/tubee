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

class Wrapper extends mysqli
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
     * construct.
     */
    public function __construct(LoggerInterface $logger, array $options = [])
    {
        $this->logger = $logger;
        $this->options = $options;
    }

    /**
     * Setup.
     */
    public function connect(): Wrapper
    {
        parent::__construct($this->dsn, $this->username, $this->password, $this->options);

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
            throw new Exception\InvalidQuery('failed to execute sql query with error '.$link->error.' ('.$link->errno.')');
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
            throw new Exception\InvalidQuery('failed to execute sql query with error '.$link->error.' ('.$link->errno.')');
        }

        return $result;
    }

    /**
     * Prepare query.
     */
    public function prepareValues(string $query, Iterable $values): mysqli_stmt
    {
        $this->logger->debug('prepare and execute sql query ['.$query.'] with values [{values}]', [
            'category' => get_class($this),
            'values' => $values,
        ]);

        $stmt = $this->prepare($query);

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
