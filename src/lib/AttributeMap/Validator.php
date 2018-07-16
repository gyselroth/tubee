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
    public function validate(array $resource): bool
    {
        foreach ($resource as $attribute => $definition) {
            if (!is_array($definition)) {
                throw new InvalidArgumentException('map attribute '.$attribute.' definition must be an array');
            }

            self::validateAttribute($attribute, $definition);
        }

        return true;
    }

    /**
     * Add attribute.
     */
    protected static function validateAttribute(string $name, array $schema): bool
    {
        $default = [
            'required' => false,
        ];

        foreach ($schema as $option => $definition) {
            switch ($option) {
                case 'from':
                case 'value':
                case 'script':
                case 'rewrite':
                case 'require_regex':
                    if (!is_string($definition)) {
                        throw new InvalidArgumentException('schema attribute '.$name.' has an invalid option '.$option.', value must be of type string');
                    }

                    $default[$option] = $definition;

                break;
                case 'required':
                    if (!is_bool($definition)) {
                        throw new InvalidArgumentException('schema attribute '.$name.' has an invalid option '.$option.', value must be of type boolean');
                    }

                    $default[$option] = $definition;

                break;
                default:
                    throw new InvalidArgumentException('schema attribute '.$name.' has an invalid option '.$option);
            }
        }

        return true;
    }
}
