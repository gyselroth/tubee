<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Xml;

class QueryTransformer
{
    /**
     * Convert mongodb like query to xpath.
     */
    public static function transform(array $query): string
    {
        $result = '';

        foreach ($query as $key => $value) {
            switch ($key) {
                case '$and':
                    //$result .= 'and';
                    foreach ($value as $sub) {
                        $result .= self::transform($sub);
                    }

                break;
                case '$or':
                    //$result .= 'or';
                    foreach ($value as $sub) {
                        $result .= self::transform($sub);
                    }

                break;
                default:
                    $result .= $key.'="'.$value.'"';

                break;
            }
        }

        return $result;
    }
}
