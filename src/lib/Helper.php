<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

class Helper
{
    /**
     * Get array value by string path.
     */
    public static function getArrayValue(Iterable $array, string $path, string $separator = '.')
    {
        if (isset($array[$path])) {
            return $array[$path];
        }
        $keys = explode($separator, $path);

        foreach ($keys as $key) {
            if (!isset($array[$key])) {
                throw new Exception('array path not found');
            }

            $array = $array[$key];
        }

        return $array;
    }

    /**
     * Remove array value by string path.
     */
    public static function deleteArrayValue(array $array, string $path, string $separator = '.')
    {
        $nodes = explode($separator, $path);
        $last = null;
        $element = &$array;
        $node = null;

        foreach ($nodes as &$node) {
            $last = &$element;
            $element = &$element[$node];
        }

        if ($last !== null) {
            unset($last[$node]);
        }

        return $array;
    }

    /**
     * Set array value via string path.
     */
    public static function setArrayValue(Iterable $array, string $path, $value, string $separator = '.')
    {
        $result = self::pathArrayToAssociative([$path => $value], $separator);

        return array_replace_recursive($array, $result);
    }

    /**
     * Convert assoc array to single array.
     */
    public static function associativeArrayToPath(Iterable $arr, Iterable $narr = [], $nkey = ''): array
    {
        /*if ($nkey !== '') {
            $narr[substr($nkey, 0, -1)] = $arr;
        }*/

        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $narr = array_merge($narr, self::associativeArrayToPath($value, $narr, $nkey.$key.'.'));
            } else {
                $narr[$nkey.$key] = $value;
            }
        }

        return $narr;
    }

    /**
     * Convert array with keys like a.b to associative array.
     */
    public static function pathArrayToAssociative(Iterable $array, string $separator = '.'): array
    {
        $out = [];
        foreach ($array as $key => $val) {
            $r = &$out;
            foreach (explode($separator, $key) as $key) {
                if (!isset($r[$key])) {
                    $r[$key] = [];
                }

                $r = &$r[$key];
            }

            $r = $val;
        }

        return $out;
    }

    /**
     * Compare array.
     */
    public static function arrayEqual(array $a1, array $a2): bool
    {
        return !array_diff($a1, $a2) && !array_diff($a2, $a1);
    }

    /**
     * Search array element.
     */
    public static function searchArray($value, $key, array $array)
    {
        foreach ($array as $k => $val) {
            if ($val[$key] == $value) {
                return $k;
            }
        }

        return null;
    }
}
