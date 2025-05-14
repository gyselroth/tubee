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

class Mattermost extends AbstractRest
{
    /**
     * Kind.
     */
    public const KIND = 'MattermostEndpoint';

    /**
     * Users URI identifier.
     */
    public const USERS_URI_IDENTIFIER = '/users';

    /**
     * Teams URI identifier.
     */
    public const TEAMS_URI_IDENTIFIER = '/teams';

    /**
     * Channels URI identifier.
     */
    public const CHANNELS_URI_IDENTIFIER = '/channels';

    /**
     * Unique filter attributes for users.
     */
    public const UNIQUE_FILTER_ATTR_USER = [
        'id',
        'username',
        'email',
    ];

    /**
     * API URI by attribute for users.
     */
    public const API_URI_BY_ATTR_USER = [
        'id' => '/',
        'username' => '/username/',
        'email' => '/email/',
    ];

    /**
     * Unique filter attributes for teams.
     */
    public const UNIQUE_FILTER_ATTR_TEAM = [
        'id',
        'name',
    ];

    /**
     * API URI by attribute for users.
     */
    public const API_URI_BY_ATTR_TEAM = [
        'id' => '/',
        'name' => '/name/',
    ];

    /**
     * Unique filter attributes default.
     */
    public const UNIQUE_FILTER_ATTR_DEFAULT = [
        'id',
    ];

    /**
     * API URI by attribute default.
     */
    public const API_URI_BY_ATTR_DEFAULT = [
        'id' => '/',
    ];

    /**
     * If disable attr is set as an attribute in worfklow, object gets disabled on endpoint.
     */
    public const DISABLE_ATTR = 'disable_object';

    /**
     * Attribute to identify if multiple users should be added to team.
     */
    public const ADD_MULTIPLE_USERS_TO_TEAM_ATTR = 'addMultipleUsers';

    /**
     * Attribute to identify if users should be removed from team.
     */
    public const REMOVE_USER_FROM_TEAM_ATTR = 'removeUsers';

    /**
     * Workflow attribute which contains users to add to a team.
     */
    public const USERS_ATTR = 'members';

    /**
     * Workflow attribute which contains user_id to remove from team.
     */
    public const USER_ATTR = 'user_id';

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

        if (strpos((string) $this->client->getConfig('base_uri'), self::TEAMS_URI_IDENTIFIER) !== false) {
            $uniqueFilter = $this->checkFilterForUniqueAttr($filterOne, 'team');
        } elseif (strpos((string) $this->client->getConfig('base_uri'), self::USERS_URI_IDENTIFIER) !== false) {
            $uniqueFilter = $this->checkFilterForUniqueAttr($filterOne, 'user');
        } else {
            $uniqueFilter = $this->checkFilterForUniqueAttr($filterOne);
        }

        $uri = $this->client->getConfig('base_uri').$uniqueFilter['uri'].$uniqueFilter['value'];
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

        return $this->build($data, $filter);
    }

    /**
     * {@inheritdoc}
     */
    public function create(AttributeMapInterface $map, array $object, bool $simulate = false): ?string
    {
        $this->logCreate($object);
        $uri = (string) $this->client->getConfig('base_uri');

        if (strpos($uri, self::CHANNELS_URI_IDENTIFIER) !== false && isset($object['type']) && $object['type'] === 'direct-channel' && isset($object['data'])) {
            if ($simulate === false) {
                $result = $this->client->post($uri.'/direct', [
                    'json' => $object['data'],
                ]);

                $body = json_decode($result->getBody()->getContents(), true);

                return $this->getResourceId($body);
            }
        } else {
            if ($simulate === false) {
                $result = $this->client->post('', [
                    'json' => $object,
                ]);

                $body = json_decode($result->getBody()->getContents(), true);

                return $this->getResourceId($body);
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function change(AttributeMapInterface $map, array $diff, array $object, EndpointObjectInterface $endpoint_object, bool $simulate = false): ?string
    {
        if (strpos((string) $this->client->getConfig('base_uri'), self::TEAMS_URI_IDENTIFIER) !== false) {
            $this->changeTeam($diff, $object, $endpoint_object, $simulate);
        } else {
            $this->changeUser($diff, $object, $endpoint_object, $simulate);
        }

        return null;
    }

    public function changeUser(array $diff, array $object, EndpointObjectInterface $endpoint_object, bool $simulate): void
    {
        $this->logger->debug('change user object on endpoint');

        if (isset($diff[self::DISABLE_ATTR])) {
            $this->disable($diff, $object, $endpoint_object, $simulate);
        }

        $uri = $this->client->getConfig('base_uri').'/'.$this->getResourceId($object, $endpoint_object).'/patch';
        $this->logChange($uri, $diff);

        if ($simulate === false) {
            $this->client->put($uri, [
                'json' => $diff,
            ]);
        }
    }

    public function changeTeam(array $diff, array $object, EndpointObjectInterface $endpoint_object, bool $simulate): void
    {
        $this->logger->debug('change team object on endpoint');

        if (isset($diff[self::ADD_MULTIPLE_USERS_TO_TEAM_ATTR])) {
            $this->logger->info('attribute ['.self::ADD_MULTIPLE_USERS_TO_TEAM_ATTR.'] is set. Add multiple users to team.');

            if ($diff[self::USERS_ATTR]) {
                $uri = $this->client->getConfig('base_uri').'/'.$this->getResourceId($object, $endpoint_object).'/members/batch';
                $this->logChange($uri, $diff);

                if ($simulate === false) {
                    $this->client->post($uri, [
                        'json' => $diff[self::USERS_ATTR],
                    ]);
                }
            } else {
                throw new MattermostException\UserAttrNotSet('attribute ['.self::USERS_ATTR.'] is not set. To add multiple users configure workflow attribute ['.self::USERS_ATTR.']');
            }
        } else {
            $this->logger->info('attribute ['.self::ADD_MULTIPLE_USERS_TO_TEAM_ATTR.'] is not set. Do not add multiple users.');
        }

        if (isset($diff[self::REMOVE_USER_FROM_TEAM_ATTR])) {
            $this->logger->info('attribute ['.self::REMOVE_USER_FROM_TEAM_ATTR.'] is set. Remove users from team.');

            foreach ($diff[self::USERS_ATTR] as $member) {
                if (isset($member[self::USER_ATTR])) {
                    $uri = $this->client->getConfig('base_uri').'/'.$this->getResourceId($object, $endpoint_object).'/members/'.$member[self::USER_ATTR];
                    $this->logChange($uri, $diff);

                    if ($simulate === false) {
                        $this->client->delete($uri);
                    }
                } else {
                    $this->logger->warning('attribute ['.self::USER_ATTR.'] is not set. Skip user [{object}]', [
                        'category' => get_class($this),
                        'object' => $member,
                    ]);
                }
            }
        } else {
            $this->logger->info('attribute ['.self::REMOVE_USER_FROM_TEAM_ATTR.'] is not set. Do not remove users.');
        }

        if (isset($diff[self::DISABLE_ATTR])) {
            $this->disable($diff, $object, $endpoint_object, $simulate);
        }
    }

    public function disable(array $diff, array $object, EndpointObjectInterface $endpoint_object, bool $simulate): void
    {
        $uri = $this->client->getConfig('base_uri').'/'.$this->getResourceId($object, $endpoint_object);

        $this->logger->info('disable mattermost object [{object}] on endpoint [{identifier}]', [
            'category' => get_class($this),
            'identifier' => $this->getIdentifier(),
            'object' => $diff,
        ]);

        if ($simulate === false) {
            $this->client->delete($uri);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(AttributeMapInterface $map, array $object, EndpointObjectInterface $endpoint_object, bool $simulate = false): bool
    {
        $uri = $this->client->getConfig('base_uri').'/'.$this->getResourceId($object, $endpoint_object).'?permanent=true';
        $this->logDelete($uri);

        if ($simulate === false) {
            $this->client->delete($uri);
        }

        return true;
    }

    /**
     * Check if unique attribute is set in filter.
     */
    public function checkFilterForUniqueAttr(array $filter, ?string $type = null): array
    {
        switch ($type) {
            case 'team':
                $uniqueFilterAttr = self::UNIQUE_FILTER_ATTR_TEAM;
                $apiUriByAttr = self::API_URI_BY_ATTR_TEAM;

                break;
            case 'user':
                $uniqueFilterAttr = self::UNIQUE_FILTER_ATTR_USER;
                $apiUriByAttr = self::API_URI_BY_ATTR_USER;

                break;
            default:
                $uniqueFilterAttr = self::UNIQUE_FILTER_ATTR_DEFAULT;
                $apiUriByAttr = self::API_URI_BY_ATTR_DEFAULT;

                break;
        }

        foreach ($uniqueFilterAttr as $attr) {
            if (isset($filter[$attr])) {
                return [
                    'attr' => $attr,
                    'value' => $filter[$attr],
                    'uri' => $apiUriByAttr[$attr],
                ];
            }
        }

        return [];
    }
}
