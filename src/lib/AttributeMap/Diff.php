<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\AttributeMap;

use Tubee\Helper;

class Diff
{
    /**
     * Create diff.
     */
    public static function calculate(array $map, array $object, array $endpoint_object): array
    {
        $diff = [];
        foreach ($map as $value) {
            $attr = $value['name'];
            $exists = isset($endpoint_object[$attr]);

            if ($value['ensure'] === AttributeMapInterface::ENSURE_EXISTS && ($exists === true || !isset($object[$attr]))) {
                continue;
            }
            if (($value['ensure'] === AttributeMapInterface::ENSURE_LAST || $value['ensure'] === AttributeMapInterface::ENSURE_EXISTS) && isset($object[$attr])) {
                if ($exists && is_array($object[$attr]) && is_array($endpoint_object[$attr]) && Helper::arrayEqual($endpoint_object[$attr], $object[$attr])) {
                    continue;
                }
                if ($exists && $object[$attr] === $endpoint_object[$attr]) {
                    continue;
                }

                $diff[$attr] = [
                    'action' => AttributeMapInterface::ACTION_REPLACE,
                    'value' => $object[$attr],
                ];
            } elseif ($value['ensure'] === AttributeMapInterface::ENSURE_ABSENT && isset($endpoint_object[$attr]) || isset($endpoint_object[$attr]) && !isset($object[$attr]) && $value['ensure'] !== AttributeMapInterface::ENSURE_MERGE) {
                $diff[$attr] = [
                    'action' => AttributeMapInterface::ACTION_REMOVE,
                ];
            } elseif ($value['ensure'] === AttributeMapInterface::ENSURE_MERGE && isset($object[$attr])) {
                $new_values = [];

                foreach ($object[$attr] as $val) {
                    if (!$exists) {
                        $new_values[] = $val;
                    } elseif (is_array($endpoint_object[$attr]) && in_array($val, $endpoint_object[$attr]) || $val === $endpoint_object[$attr]) {
                        continue;
                    } else {
                        $new_values[] = $val;
                    }
                }

                if (!empty($new_values)) {
                    $diff[$attr] = [
                        'action' => AttributeMapInterface::ACTION_ADD,
                        'value' => $new_values,
                    ];
                }
            }
        }

        return $diff;
    }
}
