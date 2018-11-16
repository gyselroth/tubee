<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Balloon;

use InvalidArgumentException;

class Validator
{
    /**
     * Validate resource.
     */
    public static function validate(array $resource): array
    {
        $defaults = [
            'data' => [
                'resource' => [
                    'options' => [],
                    'container' => null,
                    'auth' => null,
                    'oauth' => [
                        'client_id' => null,
                        'client_pw' => null,
                    ],
                    'basic' => [
                        'username' => null,
                        'password' => null,
                    ],
                ],
            ],
        ];

        if (!isset($resource['resource']['base_uri']) || !is_string($resource['resource']['base_uri'])) {
            throw new InvalidArgumentException('resource.base_uri is required and must be a string');
        }

        foreach ($resource['resource'] as $key => $value) {
            switch ($key) {
                /*case 'dsn':
                case 'username':
                case 'passwd':
                    if (!is_string($value)) {
                        throw new InvalidArgumentException("$key in pdo_options must be a string");
                    }

                break;*/
                case 'options':
                    if (!is_array($value)) {
                        throw new InvalidArgumentException("$key in pdo_options must be an array");
                    }

                break;
                default:
                    //throw new InvalidArgumentException("invalid argument $key in pdo_options provided");
            }
        }

        return $resource;
    }
}
