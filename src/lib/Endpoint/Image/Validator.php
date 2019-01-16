<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Image;

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
            'resource' => [
                'format' => null,
                'min_width' => null,
                'max_width' => null,
            ],
        ];

        if (!isset($resource['file']) || !is_string($resource['file'])) {
            throw new InvalidArgumentException('file is required and must be a string');
        }

        foreach ($resource['resource'] as $key => $value) {
            switch ($key) {
                case 'format':
                    if (!is_string($value)) {
                        throw new InvalidArgumentException("resource.$key must be a string");
                    }

                break;
                case 'min_width':
                case 'max_width':
                    if (!is_int($value)) {
                        throw new InvalidArgumentException("resource.$key must be an integer");
                    }

                    break;
                default:
                    throw new InvalidArgumentException('unknown option resource.'.$key.' provided');
            }
        }

        $resource = array_replace_recursive($defaults, $resource);
        $resource['storage'] = StorageValidator::validate($resource['storage']);

        return $resource;
    }
}
