<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Process;

use InvalidArgumentException;
use Tubee\Job\Validator as JobValidator;

class Validator extends JobValidator
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

        if (isset($resource['data']) && !is_array($resource['data'])) {
            throw new InvalidArgumentException('data must be an array');
        }

        $resource = array_intersect_key($resource, array_flip(['data']));

        return $resource;
    }
}
