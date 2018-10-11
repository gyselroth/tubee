<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\AccessRule;

use InvalidArgumentException;
use Tubee\Resource\Validator as ResourceValidator;

class Validator extends ResourceValidator
{
    /**
     * Validate resource.
     */
    public static function validate(array $resource): array
    {
        $resource = parent::validate($resource);

        foreach ($resource as $option => $value) {
            switch ($option) {
                case 'verbs':
                case 'roles':
                case 'selectors':
                case 'resources':
                    if (!is_array($value)) {
                        throw new InvalidArgumentException($option.' must be an array of strings');
                    }

                break;
            }
        }

        parent::allowOnly($resource, ['verbs', 'roles', 'selectors', 'resources']);

        return $resource;
    }
}
