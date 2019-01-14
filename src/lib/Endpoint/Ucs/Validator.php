<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Ucs;

use InvalidArgumentException;

class Validator
{
    /**
     * Validate resource.
     */
    public static function validate(array $resource): array
    {
        $defaults = [
            'options' => [
                'identifier' => '$dn$',
            ],
            'resource' => [
                'request_options' => [],
                'auth' => [
                    'username' => null,
                    'password' => null,
                ],
            ],
        ];

        if (!isset($resource['resource']['base_uri']) || !is_string($resource['resource']['base_uri'])) {
            throw new InvalidArgumentException('resource.base_uri is required and must be a valid ucs url [string]');
        }

        if (!isset($resource['resource']['flavor']) || !is_string($resource['resource']['flavor'])) {
            throw new InvalidArgumentException('resource.flavor is required and must be a valid ucs flavor');
        }

        foreach ($resource['resource'] as $key => $value) {
            switch ($key) {
                case 'request_options':
                case 'flavor':
                case 'auth':
                case 'base_uri':
                break;
                default:
                    throw new InvalidArgumentException("unknown option resource.$key provided");
            }
        }

        return array_replace_recursive($defaults, $resource);
    }
}
