<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Schema;

use InvalidArgumentException;

class Validator
{
    public static function validate(array $resource): array
    {
        foreach ($resource as $attribute => $definition) {
            if (!is_array($definition)) {
                throw new InvalidArgumentException('schema attribute '.$attribute.' definition must be an array');
            }

            $resource[$attribute] = self::validateAttribute($attribute, $definition);
        }

        return $resource;
    }

    /**
     * Add attribute.
     */
    protected static function validateAttribute(string $name, array $schema): array
    {
        $defaults = [
            'description' => null,
            'label' => null,
            'type' => null,
            'require_regex' => null,
            'required' => false,
        ];

        foreach ($schema as $option => $definition) {
            switch ($option) {
                case 'description':
                case 'label':
                case 'type':
                case 'require_regex':
                    if (!is_string($definition)) {
                        throw new InvalidArgumentException('schema attribute '.$name.' has an invalid option '.$option.', value must be of type string');
                    }

                break;
                case 'required':
                    if (!is_bool($definition)) {
                        throw new InvalidArgumentException('schema attribute '.$name.' has an invalid option '.$option.', value must be of type boolean');
                    }

                break;
                default:
                    throw new InvalidArgumentException('schema attribute '.$name.' has an invalid option '.$option);
            }
        }

        return array_replace_recursive($defaults, $schema);
    }
}
