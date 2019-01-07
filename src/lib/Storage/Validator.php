<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
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
        $defaults = ['kind' => 'StreamStorage'];
        $resource = array_replace_recursive($defaults, $resource);

        if (!isset(StorageInterface::STORAGE_MAP[$resource['kind']])) {
            throw new InvalidArgumentException('invalid storage kind provided, provide one of ['.join(',', array_flip(StorageInterface::STORAGE_MAP)).']');
        }

        $class = StorageInterface::STORAGE_MAP[$resource['kind']];
        $validator = $class.'\\Validator';
        $resource['class'] = $class;
        $resource = $validator::validate($resource);

        return $resource;
    }
}
