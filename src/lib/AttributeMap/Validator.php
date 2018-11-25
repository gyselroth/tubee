<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\AttributeMap;

use InvalidArgumentException;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class Validator
{
    /**
     * Validate resource.
     */
    public static function validate(array $resource, ExpressionLanguage $expression): array
    {
        foreach ($resource as $attribute => $definition) {
            if (!is_array($definition)) {
                throw new InvalidArgumentException('map attribute '.$attribute.' definition must be an array');
            }

            if (!is_string($attribute)) {
                throw new InvalidArgumentException('map attribute '.$attribute.' name must be a string');
            }

            $resource[$attribute] = self::validateAttribute($attribute, $definition, $expression);
        }

        return $resource;
    }

    /**
     * Validate attribute.
     */
    protected static function validateAttribute(string $name, array $schema, ExpressionLanguage $expression): array
    {
        $defaults = [
            'ensure' => AttributeMapInterface::ENSURE_LAST,
            'value' => null,
            'type' => null,
            'from' => null,
            'script' => null,
            'rewrite' => [],
            'require_regex' => null,
            'required' => false,
            'map' => [],
        ];

        foreach ($schema as $option => $definition) {
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
                break;
                case 'script':
                    $expression->evaluate($definition, []);

                break;
                case 'value':
                case 'from':
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
                    if (!is_array($definition['map'])) {
                        throw new InvalidArgumentException('map attribute '.$name.' has an invalid option '.$option.', value must be of type array');
                    }

                    if (!isset($definition['datatype'])) {
                        throw new InvalidArgumentException('mapping for attribute '.$name.' requires map.datatype');
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
}
