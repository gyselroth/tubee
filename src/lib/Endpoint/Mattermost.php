<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\EndpointObject\EndpointObjectInterface;
use Tubee\Workflow\Factory as WorkflowFactory;

class Mattermost extends AbstractRest
{
    /**
     * Kind.
     */
    public const KIND = 'MattermostEndpoint';

    /**
     * Unique filter attributes.
     */
    public const UNIQUEFILTERATTR = [
        'id',
        'username',
        'email',
    ];

    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Init endpoint.
     */
    public function __construct(string $name, string $type, Client $client, CollectionInterface $collection, WorkflowFactory $workflow, LoggerInterface $logger, array $resource = [])
    {
        $this->logger = $logger;
        parent::__construct($name, $type, $client, $collection, $workflow, $logger, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function transformQuery(?array $query = null)
    {
        if ($this->filter_all !== null && empty($query)) {
            return stripslashes($this->filter_all);
        }
        if (!empty($query)) {
            if ($this->filter_all === null) {
                return json_encode($query, JSON_UNESCAPED_UNICODE);
            }

            return '{"$and":['.stripslashes($this->filter_all).', '.json_encode($query).']}';
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function count(?array $query = null): int
    {
        $query = $this->transformQuery($query);

        $options = [];
        $options['query'] = [
            'query' => $query,
        ];

        $response = $this->client->get('', $options);

        return $this->decodeResponse($response)['total'] ?? 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(?array $query = null): Generator
    {
        $query = $this->transformQuery($query);
        $this->logGetAll($query);

        $options = [];
        $options['query'] = [
            'query' => $query,
        ];

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
        $filterOne = $this->getFilterOne($object);
        $filter = $this->transformQuery($filterOne);
        $this->logGetOne($filter);
        $uniqueFilter = $this->checkFilterForUniqueAttr($filterOne);

        if ($uniqueFilter === []) {
            $options['query'] = [
                'query' => stripslashes($filter),
            ];

            $result = $this->client->get('', $options);
            $data = $this->getResponse($result);

            if (count($data) > 1) {
                throw new Exception\ObjectMultipleFound('found more than one object with filter '.$filter);
            }

            if (count($data) === 0) {
                throw new Exception\ObjectNotFound('no object found with filter '.$filter);
            }
        } else {
            $uri = $this->client->getConfig('base_uri').'/'.$uniqueFilter['value'];
            $this->logger->debug('use attribute ['.$uniqueFilter['attr'].'] to find object on endpoint: '.$uri);

            try {
                $result = $this->client->get($uri);
            } catch (RequestException $e) {
                if ($e->getCode() === 404) {
                    throw new Exception\ObjectNotFound('no object found with filter '.$filter);
                }

                throw $e;
            }

            $data = $this->getResponse($result);
        }

        return $this->build(array_shift($data), $filter);
    }

    /**
     * Check if unique attribute is set in filter.
     */
    public function checkFilterForUniqueAttr(array $filter): array
    {
        foreach (self::UNIQUEFILTERATTR as $attr) {
            if (isset($filter[$attr])) {
                return [
                    'attr' => $attr,
                    'value' => $filter[$attr],
                    ];
            }
        }

        return [];
    }
}
