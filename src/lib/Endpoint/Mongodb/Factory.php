<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Mongodb;

use MongoDB\Client;
use Psr\Log\LoggerInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Endpoint\Mongodb as MongodbEndpoint;
use Tubee\Workflow\Factory as WorkflowFactory;

class Factory
{
    /**
     * Build instance.
     */
    public static function build(array $resource, CollectionInterface $collection, WorkflowFactory $workflow, LoggerInterface $logger): EndpointInterface
    {
        $options = $resource['data']['resource'];
        $mongodb = new Client(...$options);

        return new MongodbEndpoint($resource['name'], $resource['data']['type'], $mongodb->selectCollection($resource['data']['collection']), $collection, $workflow, $logger, $resource);
    }
}
