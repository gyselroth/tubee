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

            if (!is_string($attribute)) {
                throw new InvalidArgumentException('map attribute '.$attribute.' name must be a string');
            }

            self::validateAttribute($attribute, $definition);
        }

        return $resource;
    }

    /**
     * Validate attribute.
     */
    protected static function validateAttribute(string $name, array $schema): bool
    {
        foreach ($schema as $option => $definition) {
            switch ($option) {
                case 'value':
                break;
                case 'type':
                    if (!in_array($definition, AttributeMapInterface::VALID_TYPES)) {
                        throw new InvalidArgumentException('map attribute '.$name.' has an invalid attribute type '.$definition.', only '.implode(',', AttributeMapInterface::VALID_TYPES).' are supported');
                    }

                break;

                break;
                case 'from':
                case 'script':
                case 'rewrite':
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
                default:
                    throw new InvalidArgumentException('map attribute '.$name.' has an invalid option '.$option);
            }
        }

        return true;
    }
}
