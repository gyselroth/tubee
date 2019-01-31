<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Xml;

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
                'root_name' => 'data',
                'node_name' => 'row',
                'pretty' => true,
                'preserve_whitespace' => false,
            ],
        ];

        if (!isset($resource['file']) || !is_string($resource['file'])) {
            throw new InvalidArgumentException('file is required and must be a string');
        }

        $resource = array_replace_recursive($defaults, $resource);

        foreach ($resource['resource'] as $key => $value) {
            switch ($key) {
                case 'root_name':
                case 'node_name':
                    if (!is_string($value)) {
                        throw new InvalidArgumentException("resource.$key must be a string");
                    }

                    break;
                case 'pretty':
                case 'preserve_whitespace':
                    if (!is_bool($value)) {
                        throw new InvalidArgumentException("resource.$key must be a boolean");
                    }

                    break;
                default:
                    throw new InvalidArgumentException('unknown option resource.'.$key.' provided');
            }
        }

        $resource['storage'] = StorageValidator::validate($resource['storage']);

        return $resource;
    }
}
