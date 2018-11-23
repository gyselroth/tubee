<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Resource;

use InvalidArgumentException;

class Validator
{
    /**
     * Validate resource.
     */
    public static function validate(array $resource): array
    {
        $defaults = [
            'data' => [],
        ];

        $resource = array_replace_recursive($defaults, $resource);

        if (!isset($resource['name']) || !is_string($resource['name'])) {
            throw new InvalidArgumentException('name as string must be provided');
        }

        if (preg_match('/[^a-z\.\-\_0-9]/', $resource['name'])) {
            throw new InvalidArgumentException('resoure name can only consists from lower case alphanumeric characters and . or _ or -');
        }

        if (isset($resource['description']) && !is_string($resource['description'])) {
            throw new InvalidArgumentException('description must be a string');
        }

        if (isset($resource['data']) && !is_array($resource['data'])) {
            throw new InvalidArgumentException('data must be an array');
        }

        if (isset($resource['secrets'])) {
            if (!is_array($resource['secrets'])) {
                throw new InvalidArgumentException('secrets must be an array');
            }

            self::validateSecrets($resource['secrets']);
        }

        $resource = array_intersect_key($resource, array_flip(['name', 'data', 'description', 'secrets', 'kind']));

        return $resource;
    }

    /**
     * Validate secrets.
     */
    protected static function validateSecrets(array $secrets): array
    {
        foreach ($secrets as $key => $value) {
            if (!isset($value['secret']) || !is_string($value['secret'])) {
                throw new InvalidArgumentException('secrets.secret [string] is required');
            }

            if (!isset($value['key']) || !is_string($value['key'])) {
                throw new InvalidArgumentException('secrets.key [string] is required');
            }

            if (!isset($value['to']) || !is_string($value['to'])) {
                throw new InvalidArgumentException('secrets.to [string] is required');
            }

            if (!in_array($key, ['secret', 'key', 'to'])) {
                throw new InvalidArgumentException('secrets.'.$key.' is an unknown option');
            }
        }

        return $secrets;
    }
}
