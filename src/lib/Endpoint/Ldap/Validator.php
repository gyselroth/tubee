<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Ldap;

use InvalidArgumentException;

class Validator
{
    /**
     * Validate resource.
     */
    public static function validate(array $resource): array
    {
        $defaults = [
            'resource' => [
                'uri' => 'ldap://127.0.0.1:389',
                'binddn' => null,
                'bindpw' => null,
                'basedn' => null,
                'tls' => null,
                'options' => [],
            ],
        ];

        foreach ($resource['resource'] as $key => $value) {
            switch ($key) {
                case 'options':
                    if (!is_array($value)) {
                        throw new InvalidArgumentException("resource.$key must be an array of ldap options, see http://php.net/manual/de/function.ldap-set-option.php");
                    }

                    break;
                case 'uri':
                case 'binddn':
                case 'bindpw':
                case 'basedn':
                    if (!is_string($value)) {
                        throw new InvalidArgumentException("resource.$key must be a string");
                    }

                    break;
                case 'tls':
                    if (!is_bool($value)) {
                        throw new InvalidArgumentException("resource.$key must be a boolean");
                    }

                    break;
                default:
                    throw new InvalidArgumentException('unknown option resource.'.$key.' provided');
            }
        }

        return array_replace_recursive($defaults, $resource);
    }
}
