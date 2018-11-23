<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Storage;

use InvalidArgumentException;

class Validator
{
    /**
     * Validate resource.
     */
    public static function validate(array $resource): array
    {
        if (!isset($resource['class']) || !is_string($resource['class'])) {
            throw new InvalidArgumentException('resource.class is required and must be a string');
        }

        if (strpos($resource['class'], '\\') === false) {
            $class = 'Tubee\\Storage\\'.$resource['class'];
        } else {
            $class = $resource['class'];
        }

        if (!class_exists($class)) {
            throw new InvalidArgumentException("storage $class does not exists");
        }

        $validator = $class.'\\Validator';
        $resource['class'] = $class;
        $resource = $validator::validate($resource);

        return $resource;
    }
}
