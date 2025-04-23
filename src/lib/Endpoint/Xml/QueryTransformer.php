<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Xml;

class QueryTransformer
{
    /**
     * Convert mongodb like query to ldap query.
     */
    public static function transform($query): string
    {
        $result = '';

        if (!is_array($query)) {
            return $query;
        }

        $simple = '';
        foreach ($query as $key => $value) {
            if (is_array($value) && isset($value['$and']) || isset($value['$or'])) {
                $result .= self::transform($value);

                continue;
            }

            $key = (string) $key;
            switch ($key) {
                case '$and':
                    $part = '(';
                    foreach ($value as $sub) {
                        if ($part !== '(') {
                            $part .= ' and ';
                        }

                        $part .= self::transform($sub);
                    }

                    $result .= $part.')';

                break;
                case '$or':
                    $part = '(';
                    foreach ($value as $sub) {
                        if ($part !== '(') {
                            $part .= ' or ';
                        }

                        $part .= self::transform($sub);
                    }

                    $result .= $part.')';

                break;
                default:
                    $part = '';

                    if (is_array($value)) {
                        foreach ($value as $t => $a) {
                            if ($part !== '') {
                                $part .= ' and ';
                            }

                            if (!is_array($a) && $t[0] !== '$') {
                                $part .= "($t='$a')";
                            }

                            switch ($t) {
                                case '$gt':
                                    $part .= "($key>'$a')";

                                break;
                                case '$lt':
                                    $part .= "($key<'$a')";

                                break;
                                case '$lte':
                                    $part .= "($key<='$a')";

                                break;
                                case '$gte':
                                    $part .= "($key>='$a')";

                                break;
                                case '$ne':
                                    $part .= "($key!='$a')";

                                break;
                                case '$exists':
                                    if ($a) {
                                        $part .= "($key=*)";
                                    }

                                break;
                            }
                        }

                        if (count($value) > 1) {
                            $result .= '('.$part.')';
                        } else {
                            $result .= $part;
                        }
                    } else {
                        if ($simple !== '') {
                            $simple .= ' and ';
                        }

                        $simple .= "($key='$value')";
                    }

                break;
            }
        }

        if (!empty($simple)) {
            if (count($query) > 1) {
                $simple = '('.$simple.')';
                $result .= $simple;
            } else {
                $result .= $simple;
            }
        }

        return $result;
    }
}
