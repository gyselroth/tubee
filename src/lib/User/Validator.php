<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\User;

use InvalidArgumentException;
use Tubee\Resource\Validator as ResourceValidator;

class Validator extends ResourceValidator
{
    /**
     * Validate resource.
     */
    public static function validate(array $resource, string $policy): array
    {
        parent::validate($resource);

        if (!isset($resource['data']['password'])) {
            throw new InvalidArgumentException('data.password is required');
        }

        if (!preg_match($policy, $resource['data']['password'])) {
            throw new InvalidArgumentException('password does not match password policy '.$policy);
        }

        return $resource;
    }
}
