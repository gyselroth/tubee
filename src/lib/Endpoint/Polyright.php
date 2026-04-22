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
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\Mattermost\Exception as MattermostException;
use Tubee\EndpointObject\EndpointObjectInterface;
use Tubee\Workflow\Factory as WorkflowFactory;

class Polyright extends AbstractRest
{
    /**
     * Kind.
     */
    public const KIND = 'PolyrightEndpoint';

//    /**
//     * Persons identifier.
//     */
//    public const PERSONS_IDENTIFIER = 'persons';

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
        $return = [];

        if ($this->filter_all !== null && empty($query)) {
            $filter = json_decode(stripslashes($this->filter_all), true);
            foreach (array_shift($filter) as $item) {
                $return = array_merge($return, $item);
            }

            return $return;
        }
        if (!empty($query)) {
            if ($this->filter_all === null) {
                return $query;
            }

            $filter = json_decode(stripslashes($this->filter_all), true);
            foreach (array_shift($filter) as $item) {
                $return = array_merge($return, $item);
            }

            foreach($query as $key => $value) {
                $return[$key] = $value;
            }

            return $return;
        }

        return null;
    }

//    /**
//     * {@inheritdoc}
//     */
//    public function count(?array $query = null): int
//    {
//        $query = $this->transformQuery($query);
//
//        $options = [];
//        $options['query'] = [
//            'query' => $query,
//        ];
//
//        $response = $this->client->get('', $options);
//
//        return $this->decodeResponse($response)['total'] ?? 0;
//    }
//
    /**
     * {@inheritdoc}
     */
    public function getAll(?array $query = null): Generator
    {
        $query = $this->transformQuery($query);
        $this->logGetAll($query);

        $i = 0;
        $response = $this->client->get('');
        $data = $this->getResponse($response);

        if ($query !== [] && $query !== null) {
            $matches = array_filter(array_shift($data), function ($item) use ($query) {
                foreach ($query as $key => $value) {
                    if (!isset($item[$key]) || $item[$key] != $value) {
                        return false;
                    }
                }
                return true;
            });

            foreach ($matches as $object) {
                yield $this->build($object);
            }
        } else {
            foreach (array_shift($data) as $object) {
                yield $this->build($object);
            }
        }

        return $i;
    }

    /**
     * {@inheritdoc}
     */
    public function getOne(array $object, ?array $attributes = []): EndpointObjectInterface
    {
//        $filter = $this->transformQuery($this->getFilterOne($object));
//        $this->logGetOne($filter);
//
//        $options = [];
//        $options['query']['$filter'] = $filter;
//        $attributes[] = $this->identifier;
//        $options['query']['$select'] = join(',', $attributes);
//
//        try {
//            $result = $this->client->get('', $options);
//            $data = $this->getResponse($result);
//        } catch (RequestException $e) {
//            if ($e->getCode() === 404) {
//                throw new Exception\ObjectNotFound('no object found with filter '.$filter);
//            }
//
//            throw $e;
//        }
//
//        if (count($data) > 1) {
//            throw new Exception\ObjectMultipleFound('found more than one object with filter '.$filter);
//        }
//        if (count($data) === 0) {
//            throw new Exception\ObjectNotFound('no object found with filter '.$filter);
//        }
//
//        $data = array_shift($data);
//
//        if ($this->isGroupEndpoint()) {
//            $data = array_merge_recursive($data, $this->fetchMembers($data['id']));
//        }
//
//        return $this->build($data, $filter);
    }
//
//    /**
//     * {@inheritdoc}
//     */
//    public function create(AttributeMapInterface $map, array $object, bool $simulate = false): ?string
//    {
//        $this->logCreate($object);
//        $uri = (string) $this->client->getConfig('base_uri');
//
//        if (strpos($uri, self::CHANNELS_URI_IDENTIFIER) !== false && isset($object[self::TYPE_ATTR_FOR_DIRECT_CHANNELS]) && $object[self::TYPE_ATTR_FOR_DIRECT_CHANNELS] === self::TYPE_FOR_DIRECT_CHANNELS && isset($object['data'])) {
//            if ($simulate === false) {
//                $result = $this->client->post($uri.'/direct', [
//                    'json' => $object['data'],
//                ]);
//
//                $body = json_decode($result->getBody()->getContents(), true);
//
//                return $this->getResourceId($body);
//            }
//        } else {
//            if ($simulate === false) {
//                $result = $this->client->post('', [
//                    'json' => $object,
//                ]);
//
//                $body = json_decode($result->getBody()->getContents(), true);
//
//                return $this->getResourceId($body);
//            }
//        }
//
//        return null;
//    }
//
//    /**
//     * {@inheritdoc}
//     */
//    public function change(AttributeMapInterface $map, array $diff, array $object, EndpointObjectInterface $endpoint_object, bool $simulate = false): ?string
//    {
//        if (strpos((string) $this->client->getConfig('base_uri'), self::TEAMS_URI_IDENTIFIER) !== false) {
//            $this->changeTeam($diff, $object, $endpoint_object, $simulate);
//        } else {
//            $this->changeUser($diff, $object, $endpoint_object, $simulate);
//        }
//
//        return null;
//    }
//
//    /**
//     * {@inheritdoc}
//     */
//    public function delete(AttributeMapInterface $map, array $object, EndpointObjectInterface $endpoint_object, bool $simulate = false): bool
//    {
//        $uri = $this->client->getConfig('base_uri').'/'.$this->getResourceId($object, $endpoint_object).'?permanent=true';
//        $this->logDelete($uri);
//
//        if ($simulate === false) {
//            $this->client->delete($uri);
//        }
//
//        return true;
//    }
//
//    protected function changeUser(array $diff, array $object, EndpointObjectInterface $endpoint_object, bool $simulate): void
//    {
//        $this->logger->debug('change user object on endpoint');
//
//        if (isset($diff[self::DISABLE_ATTR])) {
//            $this->disable($diff, $object, $endpoint_object, $simulate);
//        }
//
//        $props = [];
//        $absentProps = [];
//
//        foreach ($diff as $attr => $value) {
//            if (str_contains($attr, self::PROPS_ATTR.self::PROPS_DIVIDER)) {
//                unset($diff[$attr]);
//                if ($value === null) {
//                    $absentProps[] = explode(self::PROPS_DIVIDER, $attr)[1];
//
//                    continue;
//                }
//                $props[explode(self::PROPS_DIVIDER, $attr)[1]] = $value;
//            }
//        }
//
//        if ($props !== [] || $absentProps !== []) {
//            $endpoint_props = [];
//
//            foreach ($endpoint_object->getData() as $key => $value) {
//                if (strpos($key, self::PROPS_ATTR.self::PROPS_DIVIDER) !== false) {
//                    $endpoint_props[explode(self::PROPS_DIVIDER, $key)[1]] = $value;
//                }
//            }
//
//            foreach ($absentProps as $absentProp) {
//                unset($endpoint_props[$absentProp]);
//            }
//
//            $diff[self::PROPS_ATTR] = (object) array_merge($endpoint_props, $props);
//        }
//
//        $uri = $this->client->getConfig('base_uri').'/'.$this->getResourceId($object, $endpoint_object).'/patch';
//        $this->logChange($uri, $diff);
//
//        if ($simulate === false) {
//            $this->client->put($uri, [
//                'json' => $diff,
//            ]);
//        }
//    }
//
//    protected function changeTeam(array $diff, array $object, EndpointObjectInterface $endpoint_object, bool $simulate): void
//    {
//        $this->logger->debug('change team object on endpoint');
//
//        if (isset($diff[self::ADD_MULTIPLE_USERS_TO_TEAM_ATTR])) {
//            $this->logger->info('attribute ['.self::ADD_MULTIPLE_USERS_TO_TEAM_ATTR.'] is set. Add multiple users to team.');
//
//            if ($diff[self::USERS_ATTR] && count($diff[self::USERS_ATTR]) !== 0) {
//                $uri = $this->client->getConfig('base_uri').'/'.$this->getResourceId($object, $endpoint_object).'/members/batch';
//
//                $this->batch($diff[self::USERS_ATTR], $uri, $simulate);
//            } else {
//                throw new MattermostException\UserAttrNotSet('attribute ['.self::USERS_ATTR.'] is not set or empty. To add multiple users configure workflow attribute ['.self::USERS_ATTR.']');
//            }
//        } else {
//            $this->logger->info('attribute ['.self::ADD_MULTIPLE_USERS_TO_TEAM_ATTR.'] is not set. Do not add multiple users.');
//        }
//
//        if (isset($diff[self::REMOVE_USER_FROM_TEAM_ATTR])) {
//            $this->logger->info('attribute ['.self::REMOVE_USER_FROM_TEAM_ATTR.'] is set. Remove users from team.');
//
//            foreach ($diff[self::USERS_ATTR] as $member) {
//                if (isset($member[self::USER_ATTR])) {
//                    $uri = $this->client->getConfig('base_uri').'/'.$this->getResourceId($object, $endpoint_object).'/members/'.$member[self::USER_ATTR];
//
//                    $this->logger->debug('remove user [{user}] from team [{team}]', [
//                        'category' => get_class($this),
//                        'user' => $member[self::USER_ATTR],
//                        'team' => $member['team_id'],
//                    ]);
//
//                    if ($simulate === false) {
//                        $this->client->delete($uri);
//                    }
//                } else {
//                    $this->logger->warning('attribute ['.self::USER_ATTR.'] is not set. Skip user [{object}]', [
//                        'category' => get_class($this),
//                        'object' => $member,
//                    ]);
//                }
//            }
//        } else {
//            $this->logger->info('attribute ['.self::REMOVE_USER_FROM_TEAM_ATTR.'] is not set. Do not remove users.');
//        }
//
//        if (isset($diff[self::DISABLE_ATTR])) {
//            $this->disable($diff, $object, $endpoint_object, $simulate);
//        }
//    }
//
//    protected function disable(array $diff, array $object, EndpointObjectInterface $endpoint_object, bool $simulate): void
//    {
//        $uri = $this->client->getConfig('base_uri').'/'.$this->getResourceId($object, $endpoint_object);
//
//        $this->logger->info('disable mattermost object [{object}] on endpoint [{identifier}]', [
//            'category' => get_class($this),
//            'identifier' => $this->getIdentifier(),
//            'object' => $diff,
//        ]);
//
//        if ($simulate === false) {
//            $this->client->delete($uri);
//        }
//    }
//
//    /**
//     * Check if unique attribute is set in filter.
//     */
//    protected function checkFilterForUniqueAttr(array $filter, ?string $type = null): array
//    {
//        switch ($type) {
//            case 'team':
//                $uniqueFilterAttr = self::UNIQUE_FILTER_ATTR_TEAM;
//                $apiUriByAttr = self::API_URI_BY_ATTR_TEAM;
//
//                break;
//            case 'user':
//                $uniqueFilterAttr = self::UNIQUE_FILTER_ATTR_USER;
//                $apiUriByAttr = self::API_URI_BY_ATTR_USER;
//
//                break;
//            default:
//                $uniqueFilterAttr = self::UNIQUE_FILTER_ATTR_DEFAULT;
//                $apiUriByAttr = self::API_URI_BY_ATTR_DEFAULT;
//
//                break;
//        }
//
//        foreach ($uniqueFilterAttr as $attr) {
//            if (isset($filter[$attr])) {
//                return [
//                    'attr' => $attr,
//                    'value' => $filter[$attr],
//                    'uri' => $apiUriByAttr[$attr],
//                ];
//            }
//        }
//
//        return [];
//    }
//
//    /**
//     * Batch call.
//     */
//    protected function batch(array $requests, string $uri, bool $simulate = false, bool $throw = true): array
//    {
//        $results = [];
//
//        foreach (array_chunk($requests, self::BATCH_SIZE) as $chunk) {
//            $chunk = ['json' => $chunk];
//
//            $this->logger->debug('batch request chunk [{chunk}] ', [
//                'category' => get_class($this),
//                'chunk' => $chunk,
//            ]);
//
//            if ($simulate === false) {
//                $response = $this->client->post($uri, $chunk);
//
//                $results[] = array_merge($results, $this->validateBatchResponse($response, $throw));
//            }
//        }
//
//        return $results;
//    }
//
//    /**
//     * Validate batch request.
//     */
//    protected function validateBatchResponse($response, bool $throw = true): array
//    {
//        $data = json_decode($response->getBody()->getContents(), true);
//
//        if (count($data) === 0) {
//            throw new MattermostException\BatchRequestFailed('invalid batch response data, expected array of team members');
//        }
//
//        if ($response->getStatusCode() !== 201 && $throw === true) {
//            throw new MattermostException\BatchRequestFailed('batch request part failed with http code '.$response->getStatusCode());
//        }
//
//        $this->logger->debug('batch request part succeeded with http code [{status}]', [
//            'category' => get_class($this),
//            'status' => $response->getStatusCode(),
//        ]);
//
//        return $data;
//    }
}
