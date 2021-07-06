<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2021 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Pdo;

class QueryTransformer
{
    /**
     * Filter column/table name.
     */
    public static function filterField(string $name): string
    {
        return preg_replace('/[^0-9,a-z,A-Z$_]/', '', $name);
    }

    /**
     * Convert mongodb like query to sql query.
     */
    public static function transform(array $query)
    {
        $values = [];
        $result = '';
        $simple = [];

        foreach ($query as $key => $value) {
            if (is_array($value) && isset($value['$and']) || isset($value['$or'])) {
                list($q, $v) = self::transform($value);
                $result .= $q;
                $values = array_merge($values, $v);

                continue;
            }

            $key = (string) $key;

            switch ($key) {
                case '$and':
                    $subs = [];
                    foreach ($value as $sub) {
                        list($q, $v) = self::transform($sub);
                        $subs[] = $q;
                        $values = array_merge($values, $v);
                    }

                    $result .= implode(' AND ', $subs);

                break;
                case '$or':
                    $subs = [];
                    foreach ($value as $sub) {
                        list($q, $v) = self::transform($sub);
                        $subs[] = $q;
                        $values = array_merge($values, $v);
                    }

                    $result .= implode(' OR ', $subs);

                break;
                default:
                    $parts = [];
                    if (is_array($value)) {
                        foreach ($value as $t => $a) {
                            if (!is_array($a) && $t[0] !== '$') {
                                if ($a === null) {
                                    $parts[] = '('.self::filterField($t).' IS NULL)';
                                } else {
                                    $parts[] = '('.self::filterField($t).'=?)';
                                    $values[] = $a;
                                }
                            }

                            switch ($t) {
                                case '$ne':
                                    if ($a === null) {
                                        $parts[] = '('.$key.' IS NOT NULL)';
                                    } else {
                                        $values[] = $a;
                                        $parts[] = '('.$key.'!=?)';
                                    }

                                break;
                                case '$gt':
                                    $values[] = $a;
                                    $parts[] = '('.$key.'>?)';

                                break;
                                case '$lt':
                                    $values[] = $a;
                                    $parts[] = '('.$key.'<?)';

                                break;
                                case '$lte':
                                    $values[] = $a;
                                    $parts[] = '('.$key.'<=?)';

                                break;
                                case '$gte':
                                    $values[] = $a;
                                    $parts[] = '('.$key.'>=?)';

                                break;
                            }
                        }

                        $result .= implode(' AND ', $parts);
                    } else {
                        if ($value === null) {
                            $simple[] = self::filterField($key).' IS NULL';
                        } else {
                            $simple[] = self::filterField($key).'= ?';
                            $values[] = $value;
                        }
                    }

                break;
            }
        }

        if (!empty($simple)) {
            $result .= '('.implode(' AND ', $simple).')';
        }

        return [$result, $values];
    }
}
