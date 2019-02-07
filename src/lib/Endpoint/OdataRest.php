<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use Generator;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\OdataRest\QueryTransformer;
use Tubee\EndpointObject\EndpointObjectInterface;
use Tubee\Workflow\Factory as WorkflowFactory;

class OdataRest extends AbstractRest
{
    /**
     * Kind.
     */
    public const KIND = 'OdataRestEndpoint';

    /**
     * Init endpoint.
     */
    public function __construct(string $name, string $type, Client $client, CollectionInterface $collection, WorkflowFactory $workflow, LoggerInterface $logger, array $resource = [])
    {
        $this->identifier = 'id';
        $this->container = 'value';
        parent::__construct($name, $type, $client, $collection, $workflow, $logger, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function transformQuery(?array $query = null)
    {
        $result = null;

        if ($this->filter_all !== null) {
            $result = $this->filter_all;
        }

        if (!empty($query)) {
            if ($this->filter_all === null) {
                $result = QueryTransformer::transform($query);
            } else {
                $result = $this->filter_all.' and '.QueryTransformer::transform($query);
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(?array $query = null): Generator
    {
        $this->logger->debug('find all balloon objects using ['.$this->client->getConfig('base_uri').']', [
            'category' => get_class($this),
        ]);

        $options = $this->getRequestOptions();
        $query = $this->transformQuery($query);

        if ($query !== null) {
            $options['query']['$filter'] = $query;
        }

        $i = 0;
        $response = $this->client->get('', $options);
        $data = $this->getResponse($response);

        foreach ($data as $object) {
            yield $this->build($object);
        }

        return $i;
    }

    /**
     * {@inheritdoc}
     */
    public function getOne(array $object, ?array $attributes = []): EndpointObjectInterface
    {
        $filter = $this->getFilterOne($object);
        $this->logger->debug('find rest resource with filter ['.$filter.'] in endpoint ['.$this->getIdentifier().']', [
            'category' => get_class($this),
        ]);

        $options = $this->getRequestOptions();
        $options['query']['$filter'] = $filter;
        $options['query']['$select'] = join(',', $attributes);
        $result = $this->client->get('', $options);
        $data = $this->getResponse($result);

        if (count($data) > 1) {
            throw new Exception\ObjectMultipleFound('found more than one object with filter '.$filter);
        }
        if (count($data) === 0) {
            throw new Exception\ObjectNotFound('no object found with filter '.$filter);
        }

        return $this->build(array_shift($data));
    }
}
