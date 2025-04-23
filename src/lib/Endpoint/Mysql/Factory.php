<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Mysql;

use Psr\Log\LoggerInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Endpoint\Mysql as MysqlEndpoint;
use Tubee\Endpoint\Pdo\QueryTransformer;
use Tubee\Workflow\Factory as WorkflowFactory;

class Factory
{
    /**
     * Build instance.
     */
    public static function build(array $resource, CollectionInterface $collection, WorkflowFactory $workflow, LoggerInterface $logger): EndpointInterface
    {
        $options = $resource['data']['resource'];
        $wrapper = new Wrapper($options['host'], $logger, $options['dbname'], $options['username'], $options['passwd'], $options['port'], $options['socket']);

        return new MysqlEndpoint($resource['name'], $resource['data']['type'], QueryTransformer::filterField($resource['data']['table']), $wrapper, $collection, $workflow, $logger, $resource);
    }
}
