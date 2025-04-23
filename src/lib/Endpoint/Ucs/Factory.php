<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Ucs;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Endpoint\Ucs;
use Tubee\Workflow\Factory as WorkflowFactory;

class Factory
{
    /**
     * Build instance.
     */
    public static function build(array $resource, CollectionInterface $collection, WorkflowFactory $workflow_factory, LoggerInterface $logger): EndpointInterface
    {
        $options = [
            'base_uri' => $resource['data']['resource']['base_uri'],
        ];

        $options = array_merge($resource['data']['resource']['request_options'], $options);
        $options += [
            'cookies' => true,
            'http_errors' => true,
        ];

        $client = new Client($options);

        return new Ucs($resource['name'], $resource['data']['type'], $resource['data']['resource']['flavor'], $client, $collection, $workflow_factory, $logger, $resource);
    }
}
