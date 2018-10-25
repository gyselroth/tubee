<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Mysql;

use Psr\Log\LoggerInterface;
use Tubee\DataType\DataTypeInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Endpoint\Mysql as MysqlEndpoint;
use Tubee\Workflow\Factory as WorkflowFactory;

class Factory
{
    /**
     * Build instance.
     */
    public static function build(array $resource, DataTypeInterface $datatype, WorkflowFactory $workflow, LoggerInterface $logger): EndpointInterface
    {
        $options = array_values(array_merge([
            'host' => null,
            'username' => null,
            'passwd' => null,
            'dbname' => null,
            'port' => null,
            'socket' => null,
        ], $resource['resource']));

        return new MysqlEndpoint($resource['name'], $resource['type'], $resource['table'], $datatype, $workflow, $logger, $resource);
    }
}
