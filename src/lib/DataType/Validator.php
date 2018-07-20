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
use Tubee\Resource\Validator as ResourceValidator;
use Tubee\Schema\Validator as SchemaValidator;

class Validator extends ResourceValidator
{
    /**
     * Validate resource.
     */
    public static function validate(array $resource): array
    {
        $resource = parent::validate($resource);

        if (!isset($resource['schema'])) {
            throw new InvalidArgumentException('schema must be provided');
        }

        parent::allowOnly($resource, ['schema']);
        SchemaValidator::validate($resource['schema']);

        return $resource;
    }
}
