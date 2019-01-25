<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\AttributeMap;

use InvalidArgumentException;

class Validator
{
    /**
     * Validate resource.
     */
    public static function validate(array $resource): array
    {
        foreach ($resource as $attribute => $definition) {
            if (!is_array($definition)) {
                throw new InvalidArgumentException('map attribute '.$attribute.' definition must be an array');
            }

            $resource[$attribute] = self::validateAttribute($definition);
        }

        return $resource;
    }

    /**
     * Validate attribute.
     */
    protected static function validateAttribute(array $schema): array
    {
        $defaults = [
            'ensure' => AttributeMapInterface::ENSURE_LAST,
            'value' => null,
            'type' => null,
            'name' => null,
            'from' => null,
            'script' => null,
            'unwind' => null,
            'rewrite' => [],
            'require_regex' => null,
            'required' => false,
            'map' => null,
        ];

        if (!isset($schema['name'])) {
            throw new InvalidArgumentException('attribute name is required');
        }

        $name = $schema['name'];

        foreach ($schema as $option => &$definition) {
            if (is_null($definition)) {
                continue;
            }

            switch ($option) {
                case 'ensure':
                    if (!in_array($definition, AttributeMapInterface::VALID_ENSURES)) {
                        throw new InvalidArgumentException('map.ensure as string must be provided (one of exists,last,merge,absent)');
                    }

                break;
                case 'type':
                    if (!in_array($definition, AttributeMapInterface::VALID_TYPES)) {
                        throw new InvalidArgumentException('map attribute '.$name.' has an invalid attribute type '.$definition.', only '.implode(',', AttributeMapInterface::VALID_TYPES).' are supported');
                    }

                break;
                case 'unwind':
                break;
                case 'rewrite':
                    if (!is_array($definition)) {
                        throw new InvalidArgumentException('attribute '.$option.' must be an array');
                    }

                    $definition = self::validateRewriteRules($definition);

                break;
                case 'value':
                break;
                case 'name':
                case 'from':
                case 'script':
                case 'require_regex':
                    if (!is_string($definition)) {
                        throw new InvalidArgumentException('map attribute '.$name.' has an invalid option '.$option.', value must be of type string');
                    }

                break;
                case 'required':
                    if (!is_bool($definition)) {
                        throw new InvalidArgumentException('map attribute '.$name.' has an invalid option '.$option.', value must be of type boolean');
                    }

                break;
                case 'map':
                    /*if (!is_array($definition['map'])) {
                        throw new InvalidArgumentException('map attribute '.$name.' has an invalid option '.$option.', value must be of type array');
                    }*/

                    if (!isset($definition['collection'])) {
                        throw new InvalidArgumentException('mapping for attribute '.$name.' requires map.collection');
                    }

                    if (!isset($definition['to'])) {
                        throw new InvalidArgumentException('mapping for attribute '.$name.' requires map.to');
                    }

                break;
                default:
                    throw new InvalidArgumentException('map attribute '.$name.' has an invalid option '.$option);
            }
        }

        return array_replace_recursive($defaults, $schema);
    }

    /**
     * Validate rewrite rules.
     */
    protected static function validateRewriteRules(array $rules): array
    {
        $defaults = [
            'from' => null,
            'to' => null,
            'match' => null,
        ];

        foreach ($rules as &$rule) {
            $rule = array_merge($defaults, $rule);

            foreach ($rule as $key => $value) {
                if (is_null($value)) {
                    continue;
                }

                switch ($key) {
                    case 'from':
                    case 'to':
                    case 'match':
                        if (!is_string($value)) {
                            throw new InvalidArgumentException('rewrite option '.$key.' must be a string');
                        }

                    break;
                    default:
                        throw new InvalidArgumentException('Invalid option rewrite.'.$key.' provided');
                }
            }
        }

        return $rules;
    }
}
