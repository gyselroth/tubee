<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2021 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\SqlSrvUsers;

use Psr\Log\LoggerInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Endpoint\SqlSrvUsers as SqlSrvUsersEndpoint;
use Tubee\Workflow\Factory as WorkflowFactory;

class Factory
{
    /**
     * Build instance.
     */
    public static function build(array $resource, CollectionInterface $collection, WorkflowFactory $workflow, LoggerInterface $logger): EndpointInterface
    {
        $options = $resource['data']['resource'];
        $wrapper = new Wrapper($options['host'], $logger, $options['dbname'], $options['username'], $options['password'], $options['port']);

        return new SqlSrvUsersEndpoint($resource['name'], $resource['data']['type'], $wrapper, $collection, $workflow, $logger, $resource);
    }
}
