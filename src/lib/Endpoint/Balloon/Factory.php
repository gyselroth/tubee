<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Balloon;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Tubee\DataType\DataTypeInterface;
use Tubee\Endpoint\Balloon;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Workflow\Factory as WorkflowFactory;

class Factory
{
    /**
     * Build instance.
     */
    public static function build(array $resource, DataTypeInterface $datatype, WorkflowFactory $workflow_factory, LoggerInterface $logger): EndpointInterface
    {
        $options = [
            'base_uri' => $resource['data']['resource']['base_uri'],
        ];

        if (isset($resource['data']['resource']['auth']) && $resource['data']['resource']['auth'] === 'basic') {
            $options['auth'] = [];

            if (isset($resource['data']['resource']['basic']['username'])) {
                $options['auth'][] = $resource['data']['resource']['basic']['username'];
            }

            if (isset($resource['data']['resource']['basic']['password'])) {
                $options['auth'][] = $resource['data']['resource']['basic']['password'];
            }
        }

        $client = new Client($options);

        return new Balloon($resource['name'], $resource['data']['type'], $client, $datatype, $workflow_factory, $logger, $resource);
    }
}
