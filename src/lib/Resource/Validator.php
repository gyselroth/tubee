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
        if (!isset($resource['metadata']['name']) || !is_string($resource['metadata']['name'])) {
            throw new InvalidArgumentException('metadata.name as string must be provided');
        }

        if (isset($resource['metadata']['description']) && !is_string($resource['metadata']['description'])) {
            throw new InvalidArgumentException('metadata.description must be a string');
        }

        return $resource;
    }
}
