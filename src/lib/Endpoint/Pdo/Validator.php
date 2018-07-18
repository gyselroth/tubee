<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Pdo;

use InvalidArgumentException;

class Validator
{
    /**
     * Validate resource.
     */
    public static function validate(array $resource): array
    {
        if (!isset($resource['table']) || !is_string($resource['table'])) {
            throw new InvalidArgumentException('table is required and must be a string');
        }

        if (!isset($resource['pdo_options']) || !is_array($resource['pdo_options'])) {
            throw new InvalidArgumentException('pdo_options is required and must be an array');
        }

        if (!isset($resource['pdo_options']['dsn'])) {
            throw new InvalidArgumentException('dsn in pdo_options is required');
        }

        foreach ($resource['pdo_options'] as $key => $value) {
            switch ($key) {
                case 'dsn':
                case 'username':
                case 'passwd':
                    if (!is_string($value)) {
                        throw new InvalidArgumentException("$key in pdo_options must be a string");
                    }

                break;
                case 'options':
                    if (!is_array($value)) {
                        throw new InvalidArgumentException("$key in pdo_options must be an array");
                    }

                break;
                default:
                    throw new InvalidArgumentException("invalid argument $key in pdo_options provided");
            }
        }

        return $resource;
    }
}
