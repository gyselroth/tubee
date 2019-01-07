<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Storage\Smb;

use InvalidArgumentException;

class Validator
{
    /**
     * Validate resource.
     */
    public static function validate(array $resource): array
    {
        if (!isset($resource['host']) || !is_string($resource['host'])) {
            throw new InvalidArgumentException('resource.host is required and must be a string');
        }

        if (isset($resource['username']) && !is_string($resource['username'])) {
            throw new InvalidArgumentException('resource.username must be a string');
        }

        if (!isset($resource['password']) || !is_string($resource['password'])) {
            throw new InvalidArgumentException('resource.password must be a string');
        }

        return $resource;
    }
}
