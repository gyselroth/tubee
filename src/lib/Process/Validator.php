<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Process;

use Tubee\Job\Validator as JobValidator;

class Validator extends JobValidator
{
    /**
     * Validate resource.
     */
    public static function validate(array $resource): array
    {
        $resource['name'] = 'dummy';
        $resource = parent::validate($resource);
        unset($resource['name']);

        return $resource;
    }
}