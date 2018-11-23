<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Mongodb;

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
                'uri' => 'mongodb://127.0.0.1',
                'uri_options' => [],
                'driver_options' => [],
            ],
        ];

        if (!isset($resource['collection']) || !is_string($resource['collection'])) {
            throw new InvalidArgumentException('resource.collection is required and must be a string');
        }

        foreach ($resource['resource'] as $key => $value) {
            switch ($key) {
                case 'uri':
                    if (!is_string($value)) {
                        throw new InvalidArgumentException("resource.$key must be a valid mongodb uri [string]");
                    }

                break;
                case 'uri_options':
                case 'driver_options':
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
