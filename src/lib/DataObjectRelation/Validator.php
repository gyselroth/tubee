<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\DataObjectRelation;

use InvalidArgumentException;
use Tubee\Resource\Validator as ResourceValidator;

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
                'relation' => [],
                'context' => [],
            ],
        ];

        $resource = array_replace_recursive($defaults, $resource);

        if (!is_array($resource['data']['relation']) || count($resource['data']['relation']) !== 2) {
            throw new InvalidArgumentException('relation requires data.relation with exactly two objects');
        }

        self::validateRelation($resource['data']['relation']);

        if (!is_array($resource['data']['context'])) {
            throw new InvalidArgumentException('relation requires data.context to be an array');
        }

        return $resource;
    }

    /**
     * Validate relation.
     */
    protected static function validateRelation(array $resource): void
    {
        foreach ($resource as $object) {
            if (['namespace', 'collection', 'object'] != array_keys($object)) {
                throw new InvalidArgumentException('relation in data.relation must contain namespace, collection and object as strings');
            }

            if (array_filter($object, 'is_string') != $object) {
                throw new InvalidArgumentException('relation in data.relation must contain namespace, collection and object as strings');
            }
        }
    }
}
