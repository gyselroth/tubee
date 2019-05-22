<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Pdo;

class QueryTransformer
{
    /**
     * Convert mongodb like query to sql query.
     */
    public static function transform(array $query): string
    {
        $result = '';
        $simple = [];

        foreach ($query as $key => $value) {
            if (is_array($value) && isset($value['$and']) || isset($value['$or'])) {
                $result .= self::transform($value);

                continue;
            }

            $key = (string) $key;

            switch ($key) {
                case '$and':
                    $subs = [];
                    foreach ($value as $sub) {
                        $subs[] = self::transform($sub);
                    }

                    $result .= implode(' AND ', $subs);

                break;
                case '$or':
                    $subs = [];
                    foreach ($value as $sub) {
                        $subs[] = self::transform($sub);
                    }

                    $result .= implode(' OR ', $subs);

                break;
                default:
                    $parts = [];
                    if (is_array($value)) {
                        foreach ($value as $t => $a) {
                            if (!is_array($a) && $t[0] !== '$') {
                                $parts[] = '('.$t.'='.$a.')';
                            }
                        }

                        $result .= implode(' AND ', $parts);
                    } else {
                        $simple[] = $key.'=\''.$value.'\'';
                    }

                break;
            }
        }

        if (!empty($simple)) {
            $result .= '('.implode(' AND ', $simple).')';
        }

        return $result;
    }
}
