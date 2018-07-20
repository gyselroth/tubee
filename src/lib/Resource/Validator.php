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
        if (!isset($resource['name']) || !is_string($resource['name'])) {
            throw new InvalidArgumentException('name as string must be provided');
        }

        if (isset($resource['description']) && !is_string($resource['description'])) {
            throw new InvalidArgumentException('description must be a string');
        }

        return $resource;
    }

    /**
     * Allow only.
     */
    public static function allowOnly(array $resource, array $attributes): bool
    {
        $allow = array_merge(['name', 'description'], $attributes);
        foreach ($resource as $attribute => $value) {
            if (!in_array($attribute, $allow)) {
                throw new InvalidArgumentException('given attribute '.$attribute.' is not valid at this place');
            }
        }

        return true;
    }
}