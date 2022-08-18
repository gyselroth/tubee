<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2022 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Job;

use Tubee\Helper;

class Validator
{
    /**
     * Validate resource.
     */
    public static function validate(array $resource): array
    {
        if (isset($resource['data']['filter'])) {
            Helper::jsonDecode(stripslashes($resource['data']['filter']), true);
        }

        return $resource;
    }
}
