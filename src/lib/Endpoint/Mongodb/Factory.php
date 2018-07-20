<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Mongodb;

use MongoDB\Client;
use Psr\Log\LoggerInterface;
use Tubee\DataType\DataTypeInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Endpoint\Mongodb as MongodbEndpoint;
use Tubee\Workflow\Factory as WorkflowFactory;

class Factory
{
    /**
     * Build instance.
     */
    public static function build(array $resource, DataTypeInterface $datatype, WorkflowFactory $workflow, LoggerInterface $logger): EndpointInterface
    {
        $options = array_values(array_merge([
            'uri' => 'mongodb://127.0.0.1',
            'uri_options' => null,
            'driver_options' => null,
        ], $resource['resource']));

        $mongodb = new Client(...$options);

        return new MongodbEndpoint($resource['name'], $resource['type'], $mongodb->selectCollection($resource['collection']), $storage, $datatype, $workflow, $logger, $resource);
    }
}
