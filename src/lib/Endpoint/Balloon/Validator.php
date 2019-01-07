<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
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
            'resource' => [
                'options' => [],
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
        ];

        if (!isset($resource['resource']['base_uri']) || !is_string($resource['resource']['base_uri'])) {
            throw new InvalidArgumentException('resource.base_uri is required and must be a valid balloon url [string]');
        }

        foreach ($resource['resource'] as $key => $value) {
            switch ($key) {
                case 'auth':
                    if ($value !== 'basic' && $value !== 'oauth') {
                        throw new InvalidArgumentException('resource.auth must be either basic or oauth');
                    }

                break;
                case 'options':
                case 'base_uri':
                case 'oauth':
                case 'basic':
                break;
                default:
                    throw new InvalidArgumentException("unknown option resource.$key provided");
            }
        }

        return array_replace_recursive($defaults, $resource);
    }
}
