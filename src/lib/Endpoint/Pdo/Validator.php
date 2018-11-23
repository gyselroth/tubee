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
        $defaults = [
            'resource' => [
                'username' => null,
                'passwd' => null,
                'options' => [],
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
                case 'dsn':
                case 'username':
                case 'passwd':
                    if (!is_string($value)) {
                        throw new InvalidArgumentException("resource.$key must be a string");
                    }

                break;
                case 'options':
                    if (!is_array($value)) {
                        throw new InvalidArgumentException("resource.$key must be an array");
                    }

                break;
                default:
                    throw new InvalidArgumentException("invalid argument resource.$key provided");
            }
        }

        return array_replace_recursive($defaults, $resource);
    }
}
