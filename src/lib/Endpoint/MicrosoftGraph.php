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
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\MicrosoftGraph\Exception as GraphException;
use Tubee\Endpoint\OdataRest\QueryTransformer;
use Tubee\EndpointObject\EndpointObjectInterface;
use Tubee\Helper;
use Tubee\Workflow\Factory as WorkflowFactory;

class MicrosoftGraph extends OdataRest
{
    /**
     * Kind.
     */
    public const KIND = 'MicrosoftGraphEndpoint';

    /**
     * API Root Endpoint.
     */
    public const API_ENDPOINT = 'https://graph.microsoft.com/v1.0';

    /**
     * Batch Endpoint.
     */
    public const BATCH_ENDPOINT = 'https://graph.microsoft.com/v1.0/$batch';

    /**
     * Graph API batch size limit.
     */
    public const BATCH_SIZE = 20;

    /**
     * Init endpoint.
     */
    public function __construct(string $name, string $type, Client $client, CollectionInterface $collection, WorkflowFactory $workflow, LoggerInterface $logger, array $resource = [])
    {
        parent::__construct($name, $type, $client, $collection, $workflow, $logger, $resource, 'value');
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
            if ($this->isGroupEndpoint()) {
                $object = array_merge($object, $this->fetchMembers($object['id']));
            }

            yield $this->build($object);
        }

        return $i;
    }

    /**
     * {@inheritdoc}
     */
    public function create(AttributeMapInterface $map, array $object, bool $simulate = false): ?string
    {
        $requests = [];
        $new = $object;

        unset($object['owners'], $object['members']);

        $id = parent::create($map, $object, $simulate);

        if ($this->isGroupEndpoint()) {
            $requests = $this->getMemberChangeBatchRequests($id, $new, [], $map->getMap());
        }

        if ($this->isGroupEndpoint() && isset($object['resourceProvisioningOptions']) && in_array('Team', $object['resourceProvisioningOptions'])) {
            $requests[] = [
                'id' => 'create-team',
                'url' => 'groups/'.$id.'/team',
                'method' => 'PUT',
                'body' => new \stdClass(),
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ];
        }

        if (count($requests) !== 0) {
            $this->batch($requests, $simulate);
        }

        return $id;
    }

    /**
     * {@inheritdoc}
     */
    public function change(AttributeMapInterface $map, array $diff, array $object, EndpointObjectInterface $endpoint_object, bool $simulate = false): ?string
    {
        $id = $this->getResourceId($object, $endpoint_object);
        $endpoint_object = $endpoint_object->getData();
        $uri = $this->client->getConfig('base_uri').'/'.$id;
        $requests = [];

        if ($this->isGroupEndpoint()) {
            if (isset($object['resourceProvisioningOptions'])
              && in_array('Team', $object['resourceProvisioningOptions'])
              && isset($endpoint_object['resourceProvisioningOptions'])
              && !in_array('Team', $endpoint_object['resourceProvisioningOptions'])) {
                $requests[] = [
                    'id' => 'create-team',
                    'method' => 'PUT',
                    'url' => '/groups/'.$id.'/team',
                    'body' => new \stdClass(),
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                ];
            }

            $requests = array_merge($requests, $this->getMemberChangeBatchRequests($id, $diff, $endpoint_object, $map->getMap()));
            unset($diff['members'], $diff['owners']);
        }

        if (count($requests) === 0) {
            $this->logChange($uri, $diff);

            if (count($diff) !== 0 && $simulate === false) {
                $this->client->patch($uri, [
                    'json' => $diff,
                ]);
            }
        } else {
            $request = [];

            if (count($diff) !== 0) {
                $request = [[
                    'id' => 'change',
                    'method' => 'PATCH',
                    'url' => substr($uri, strlen(self::API_ENDPOINT)),
                    'body' => $diff,
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                ]];
            }

            $this->logChange(self::BATCH_ENDPOINT, $requests);
            $this->batch(array_merge($request, $requests), $simulate);
        }

        return null;
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

        $data = array_shift($data);

        if ($this->isGroupEndpoint()) {
            $data = array_merge_recursive($data, $this->fetchMembers($data['id']));
        }

        return $this->build($data, $filter);
    }

    /**
     * Check if endpoint holds group objects.
     */
    protected function isGroupEndpoint(): bool
    {
        $url = (string) $this->client->getConfig('base_uri');

        if ($url === 'https://graph.microsoft.com/v1.0/groups' || $url === 'https://graph.microsoft.com/beta/groups') {
            return true;
        }

        return false;
    }

    /**
     * Get member batch requests.
     */
    protected function getMemberChangeBatchRequests(string $id, array $diff, array $endpoint_object, array $map): array
    {
        $requests = [];

        foreach (['members', 'owners'] as $type) {
            if (!isset($diff[$type])) {
                $this->logger->info('attribute [{attribute}] not in diff (no update required), skip group member batching', [
                    'category' => get_class($this),
                    'attribute' => $type,
                ]);

                continue;
            }

            $add = $diff[$type];
            $remove = [];

            if (isset($endpoint_object[$type])) {
                $add = array_diff($diff[$type], $endpoint_object[$type]);
                $remove = array_diff($endpoint_object[$type], $diff[$type]);
            }

            foreach ($add as $member) {
                $requests[] = [
                    'id' => 'add-'.$type.'-'.$member,
                    'method' => 'POST',
                    'url' => '/groups/'.$id.'/'.$type.'/$ref',
                    'body' => [
                        '@odata.id' => self::API_ENDPOINT.'/directoryObjects/'.$member,
                    ],
                     'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                ];
            }

            $array_key = Helper::searchArray($type, 'name', $map);

            if ($array_key !== null && $map[$array_key]['ensure'] !== 'merge') {
                foreach ($remove as $member) {
                    $requests[] = [
                        'id' => 'remove-'.$type.'-'.$member,
                        'method' => 'DELETE',
                        'url' => '/groups/'.$id.'/'.$type.'/'.$member.'/$ref',
                        'headers' => [
                            'Content-Type' => 'application/json',
                        ],
                    ];
                }
            }
        }

        return $requests;
    }

    /**
     * Get object url.
     */
    protected function getObjectUrl(array $objects): array
    {
        foreach ($objects as &$object) {
            $object = self::API_ENDPOINT.'/directoryObjects/'.$object;
        }

        return $objects;
    }

    /**
     * Batch call.
     */
    protected function batch(array $requests, bool $simulate = false, bool $throw = true): array
    {
        $results = [];

        foreach (array_chunk($requests, self::BATCH_SIZE) as $chunk) {
            $chunk = ['requests' => $chunk];

            $this->logger->debug('batch request chunk [{chunk}] ', [
                'category' => get_class($this),
                'chunk' => $chunk,
            ]);

            if ($simulate === false) {
                $response = $this->client->post(self::BATCH_ENDPOINT, [
                    'json' => $chunk,
                ]);

                $results = array_merge($results, $this->validateBatchResponse($response, $throw));
            }
        }

        return $results;
    }

    /**
     * Validate batch request.
     */
    protected function validateBatchResponse($response, bool $throw = true): array
    {
        $data = json_decode($response->getBody()->getContents(), true);

        if (!isset($data['responses'])) {
            throw new GraphException\BatchRequestFailed('invalid batch response data, expected responses list');
        }

        foreach ($data['responses'] as $result) {
            $this->logger->debug('validate batch request id [{id}]', [
                'category' => get_class($this),
                'id' => $result['id'],
            ]);

            if (isset($result['body']['error']) && $throw === true) {
                throw new GraphException\BatchRequestFailed('batch request part failed with error '.$result['body']['error']['message'].' and http code '.$result['status']);
            }

            $this->logger->debug('batch request part [{request}] succeeded with http code [{status}]', [
                'category' => get_class($this),
                'request' => $result['id'] ?? null,
                'status' => $result['status'] ?? null,
            ]);
        }

        return $data['responses'];
    }

    /**
     * Fetch members.
     */
    protected function fetchMembers(string $id): array
    {
        $requests = [
            [
                'id' => 'members',
                'method' => 'GET',
                'url' => '/groups/'.$id.'/members',
            ],
            [
                'id' => 'owners',
                'method' => 'GET',
                'url' => '/groups/'.$id.'/owners',
            ],
            [
                'id' => 'team',
                'method' => 'GET',
                'url' => '/groups/'.$id.'/team',
            ],
        ];

        $this->logger->debug('fetch group members from batch request [{requests}]', [
            'class' => get_class($this),
            'requests' => $requests,
        ]);

        $data = $this->batch($requests, false, false);

        $set = [
            'owners' => [],
            'members' => [],
            'resourceProvisioningOptions' => [],
        ];

        foreach ($data as $response) {
            switch ($response['id']) {
                case 'owners':
                case 'members':
                default:
                    foreach ($response['body'][$this->container] as $record) {
                        $set[$response['id']][] = $record['id'];
                    }

                    $id = $response['id'];
                    $response = $response['body'];
                    while (isset($response['@odata.nextLink'])) {
                        $response = $this->decodeResponse($this->client->get($response['@odata.nextLink']));
                        $set[$id] = array_merge($set[$id], array_column($response[$this->container], 'id'));
                    }

                break;
                case 'team':
                    if ($response['status'] === 200) {
                        $set['resourceProvisioningOptions'][] = 'Team';
                    }

                break;
            }
        }

        return $set;
    }
}
