<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Balloon;

use Psr\Log\LoggerInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\Balloon;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Endpoint\Rest\Factory as RestFactory;
use Tubee\Workflow\Factory as WorkflowFactory;

class Factory
{
    /**
     * Build instance.
     */
    public static function build(array $resource, CollectionInterface $collection, WorkflowFactory $workflow_factory, LoggerInterface $logger): EndpointInterface
    {
        $client = RestFactory::buildClient($resource, $logger);

        return new Balloon($resource['name'], $resource['data']['type'], $client, $collection, $workflow_factory, $logger, $resource);
    }
}
