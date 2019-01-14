<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Collection;

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
        $defaults = [
            'data' => [
                'schema' => [],
            ],
        ];

        $resource = array_replace_recursive($defaults, $resource);

        if (!is_array($resource['data']['schema'])) {
            throw new InvalidArgumentException('data.schema must be an array');
        }

        $resource['data']['schema'] = SchemaValidator::validate($resource['data']['schema']);

        return $resource;
    }
}
