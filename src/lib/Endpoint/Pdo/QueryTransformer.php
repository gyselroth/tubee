<?php

declare(strict_types=1);

/**
 * tubee.io
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

        foreach ($query as $key => $value) {
            switch ($key) {
                case '$and':
                    $result .= '(';
                    foreach ($value as $sub) {
                        $result .= 'AND '.self::transform($sub);
                    }
                    $result .= ')';

                break;
                case '$or':
                    $result .= '(';
                    foreach ($value as $sub) {
                        $result .= 'OR '.self::transform($sub);
                    }
                    $result .= ')';

                break;
                default:
                    $result .= $key.'=\''.$value.'\'';

                break;
            }
        }

        return $result;
    }
}
