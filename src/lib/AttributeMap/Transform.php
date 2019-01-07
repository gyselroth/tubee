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
use MongoDB\BSON\Binary;

class Transform
{
    /**
     * Convert value.
     */
    public static function convertType($value, string $attribute, string $type)
    {
        switch ($type) {
            case AttributeMapInterface::TYPE_ARRAY:
                return (array) $value;

            break;
            case AttributeMapInterface::TYPE_STRING:
                return (string) $value;

            break;
            case AttributeMapInterface::TYPE_INT:
                return (int) $value;

            break;
            case AttributeMapInterface::TYPE_BOOL:
                return (bool) $value;

            break;
            case AttributeMapInterface::TYPE_FLOAT:
                return (float) $value;

            break;
            case AttributeMapInterface::TYPE_NULL:
                return null;

            break;
            case AttributeMapInterface::TYPE_BINARY:
                return new Binary($value, Binary::TYPE_GENERIC);

            break;
            default:
                if (is_object($value)) {
                    return $value;
                }

                throw new InvalidArgumentException('invalid type set for attribute '.$attribute);
        }
    }
}
