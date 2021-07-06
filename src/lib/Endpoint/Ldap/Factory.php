<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2021 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Ldap;

use Dreamscapes\Ldap\Core\Ldap as LdapServer;
use Psr\Log\LoggerInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Endpoint\Ldap as LdapEndpoint;
use Tubee\Workflow\Factory as WorkflowFactory;

class Factory
{
    /**
     * Build instance.
     */
    public static function build(array $resource, CollectionInterface $collection, WorkflowFactory $workflow, LoggerInterface $logger): EndpointInterface
    {
        $ldap = new LdapServer();

        return new LdapEndpoint($resource['name'], $resource['data']['type'], $ldap, $collection, $workflow, $logger, $resource);
    }
}
