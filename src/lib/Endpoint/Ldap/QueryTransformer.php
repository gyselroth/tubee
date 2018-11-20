<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Ldap;

class QueryTransformer
{
    /**
     * Convert mongodb like query to ldap query.
     */
    public static function transform(array $query): string
    {
        $result = '&';

        foreach ($query as $key => $value) {
            switch ($key) {
                case '$and':
                    $result .= '&';
                    foreach ($value as $sub) {
                        $result .= '('.self::transform($sub).')';
                    }

                break;
                case '$or':
                    $result .= '|';
                    foreach ($value as $sub) {
                        $result .= '('.self::transform($sub).')';
                    }

                break;
                default:
                    $result .= '('.$key.'='.$value.')';

                break;
            }
        }

        return $result;
    }
}
