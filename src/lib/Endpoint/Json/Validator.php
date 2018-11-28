<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Json;

use InvalidArgumentException;
use Tubee\Storage\Validator as StorageValidator;

class Validator
{
    /**
     * Validate resource.
     */
    public static function validate(array $resource): array
    {
        $defaults = [
            'storage' => [
                'kind' => 'StreamStorage',
            ],
        ];

        if (!isset($resource['file']) || !is_string($resource['file'])) {
            throw new InvalidArgumentException('file is required and must be a string');
        }

        $resource = array_replace_recursive($defaults, $resource);
        $resource['storage'] = StorageValidator::validate($resource['storage']);

        return $resource;
    }
}
