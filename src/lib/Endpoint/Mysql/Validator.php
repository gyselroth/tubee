<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Mysql;

use InvalidArgumentException;

class Validator
{
    /**
     * Validate resource.
     */
    public static function validate(array $resource): array
    {
        $defaults = [
            'resource' => [
                'host' => '127.0.0.1',
                'username' => null,
                'passwd' => null,
                'dbname' => null,
                'port' => 3306,
                'socket' => null,
            ],
        ];

        if (!isset($resource['table']) || !is_string($resource['table'])) {
            throw new InvalidArgumentException('table is required and must be a string');
        }

        if (!isset($resource['resource']['dsn'])) {
            throw new InvalidArgumentException('resource.dsn is required');
        }

        foreach ($resource['resource'] as $key => $value) {
            switch ($key) {
                case 'host':
                case 'dbname':
                case 'socket':
                case 'username':
                case 'passwd':
                    if (!is_string($value)) {
                        throw new InvalidArgumentException("resource.$key must be a string");
                    }

                break;
                case 'port':
                    if (!is_int($value) || $value < 0 || $value > 65535) {
                        throw new InvalidArgumentException("resource.$key must be a valid tcp port (0-65535)");
                    }

                break;
                default:
                    throw new InvalidArgumentException("invalid argument resource.$key provided");
            }
        }

        return array_replace_recursive($defaults, $resource);
    }
}
