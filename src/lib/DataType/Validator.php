<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\DataType;

use InvalidArgumentException;
use Tubee\Schema\Validator as SchemaValidator;

class Validator
{
    /**
     * Validate resource.
     */
    public static function validate(array $resource): bool
    {
        if (!isset($resource['name']) || !is_string($resource['name'])) {
            throw new InvalidArgumentException('A name as string must be provided');
        }

        if (isset($resource['description']) && !is_string($resource['description'])) {
            throw new InvalidArgumentException('Description must be a string');
        }

        if (!isset($resource['schema'])) {
            throw new InvalidArgumentException('A schema must be provided');
        }
        SchemaValidator::validate($resource['schema']);

        return true;
    }
}
