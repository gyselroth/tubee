<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2022 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Json;

use Psr\Log\LoggerInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Endpoint\Json as JsonEndpoint;
use Tubee\Storage\Factory as StorageFactory;
use Tubee\Workflow\Factory as WorkflowFactory;

class Factory
{
    /**
     * Build instance.
     */
    public static function build(array $resource, CollectionInterface $collection, WorkflowFactory $workflow, LoggerInterface $logger): EndpointInterface
    {
        $storage = StorageFactory::build($resource['data']['storage'], $logger);

        return new JsonEndpoint($resource['name'], $resource['data']['type'], $resource['data']['file'], $storage, $collection, $workflow, $logger, $resource);
    }
}
