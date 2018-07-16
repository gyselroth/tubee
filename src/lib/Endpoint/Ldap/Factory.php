<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Ldap\Factory;

use Dreamscapes\Ldap\Core\Ldap as LdapServer;
use Psr\Log\LoggerInterface;
use Tubee\DataType\DataTypeInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Endpoint\Ldap;

class Factory
{
    /**
     * Build instance.
     */
    public static function build(array $resource, DataTypeInterface $datatype, LoggerInterface $logger): EndpointInterface
    {
        $ldap = new LdapServer();

        return new Ldap($resource, $ldap, $datatype, $logger);
    }
}
