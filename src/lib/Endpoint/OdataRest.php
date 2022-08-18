<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2022 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\OdataRest\QueryTransformer;
use Tubee\Endpoint\Rest\Exception as RestException;
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
    public function __construct(string $name, string $type, Client $client, CollectionInterface $collection, WorkflowFactory $workflow, LoggerInterface $logger, array $resource = [], ?string $container = null)
    {
        $this->setContainer($container);
        parent::__construct($name, $type, $client, $collection, $workflow, $logger, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function transformQuery(?array $query = null)
    {
        if ($this->filter_all !== null) {
            return QueryTransformer::transform($this->getFilterAll());
        }
        if (!empty($query)) {
            if ($this->filter_all === null) {
                return QueryTransformer::transform($query);
            }

            return QueryTransformer::transform([
                    '$and' => [
                        $this->getFilterAll(),
                        $query,
                    ],
                ]);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function count(?array $query = null): int
    {
        $options = [];
        $query = $this->transformQuery($query);

        if ($query !== null) {
            $options['query']['$filter'] = $query;
        }

        $response = $this->client->get('', $options);
        $data = $this->decodeResponse($response);
        if (isset($this->container) && $this->container !== null) {
            if (isset($data[$this->container])) {
                $data = $data[$this->container];
            } else {
                throw new RestException\InvalidContainer('specified container '.$this->container.' does not exists in response');
            }
        }

        return count($data);
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(?array $query = null): Generator
    {
        $options = [];
        $query = $this->transformQuery($query);
        $this->logGetAll($query);

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
        $filter = $this->transformQuery($this->getFilterOne($object));
        $this->logGetOne($filter);

        $options = [];
        $options['query']['$filter'] = $filter;
        $attributes[] = $this->identifier;
        $options['query']['$select'] = join(',', $attributes);

        try {
            $result = $this->client->get('', $options);
            $data = $this->getResponse($result);
        } catch (RequestException $e) {
            if ($e->getCode() === 404) {
                throw new Exception\ObjectNotFound('no object found with filter '.$filter);
            }

            throw $e;
        }

        if (count($data) > 1) {
            throw new Exception\ObjectMultipleFound('found more than one object with filter '.$filter);
        }
        if (count($data) === 0) {
            throw new Exception\ObjectNotFound('no object found with filter '.$filter);
        }

        return $this->build(array_shift($data), $filter);
    }

    protected function setContainer(?string $container): void
    {
        $this->container = $container;
    }
}
