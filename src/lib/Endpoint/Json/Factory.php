<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Json;

use Psr\Log\LoggerInterface;
use Tubee\DataType\DataTypeInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Endpoint\Json as JsonEndpoint;
use Tubee\Storage\Factory as StorageFactory;
use Tubee\Workflow\Factory as WorkflowFactory;

class Factory
{
    /**
     * Build instance.
     */
    public static function build(array $resource, DataTypeInterface $datatype, WorkflowFactory $workflow, LoggerInterface $logger): EndpointInterface
    {
        $storage = StorageFactory::build($resource['resource'], $logger);

        return new JsonEndpoint($resource['name'], $resource['type'], $resource['file'], $storage, $datatype, $workflow, $logger, $resource);
    }
}