<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2021 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use Generator;
use GuzzleHttp\Client;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\Ucs\Exception as UcsException;
use Tubee\EndpointObject\EndpointObjectInterface;
use Tubee\Workflow\Factory as WorkflowFactory;

class Ucs extends AbstractEndpoint
{
    use LoggerTrait;

    /**
     * Kind.
     */
    public const KIND = 'UcsEndpoint';

    /**
     * Constants.
     */
    public const ATTR_DN = '$dn$';
    public const SESSION_COOKIE_NAME = 'UMCSessionId';

    /**
     * Guzzle client.
     *
     * @var Client
     */
    protected $client;

    /**
     * UCS session ID.
     *
     * @var string
     */
    protected $session;

    /**
     * Object type.
     *
     * @var string
     */
    protected $flavor;

    /**
     * Init endpoint.
     */
    public function __construct(string $name, string $type, string $flavor, Client $client, CollectionInterface $collection, WorkflowFactory $workflow, LoggerInterface $logger, array $resource = [])
    {
        $this->client = $client;
        $this->flavor = $flavor;
        $this->identifier = self::ATTR_DN;
        parent::__construct($name, $type, $collection, $workflow, $logger, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function setup(bool $simulate = false): EndpointInterface
    {
        $auth = $this->resource['data']['resource']['auth'];

        $url = $this->resource['data']['resource']['base_uri'].'/auth';
        $this->logger->debug('create ucs auth session from ['.$url.']', [
            'category' => get_class($this),
        ]);

        $response = $this->client->post($url, [
            'json' => [
                'options' => [
                    'username' => $auth['username'],
                    'password' => $auth['password'],
                ],
            ],
        ]);

        $body = json_decode($response->getBody()->getContents(), true);
        $this->session = $this->getSessionId();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown(bool $simulate = false): EndpointInterface
    {
        $this->client->getConfig('cookies')->clear();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(AttributeMapInterface $map, array $object, EndpointObjectInterface $endpoint_object, bool $simulate = false): bool
    {
        $url = $this->client->getConfig('base_uri').'/command/udm/remove';
        $endpoint_object = $endpoint_object->getData();
        $resource = $this->getResourceId($object, $endpoint_object);
        $this->logDelete($resource);

        if ($simulate === false) {
            $result = $this->parse($this->client->post($url, $this->getRequestOptions([
                'json' => [
                    'options' => [
                        [
                            'object' => $resource,
                            'options' => [
                                'cleanup' => true,
                            ],
                        ],
                    ],
                ],
            ])));

            $result = array_shift($result);
            $this->verifyWriteResult($result);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function create(AttributeMapInterface $map, array $object, bool $simulate = false): ?string
    {
        $url = $this->client->getConfig('base_uri').'/command/udm/add';

        $dn = explode(',', $this->getResourceId($object));
        array_shift($dn);
        $container = implode(',', $dn);
        unset($object[self::ATTR_DN]);
        $this->logCreate($object);

        if ($simulate === false) {
            $result = $this->parse($this->client->post($url, $this->getRequestOptions([
                'json' => [
                    'options' => [
                        [
                            'options' => [
                                'objectType' => $this->flavor,
                                'container' => $container,
                            ],
                            'object' => $object,
                        ],
                    ],
                ],
            ])));

            $result = array_shift($result);
            $this->verifyWriteResult($result);

            return $this->getResourceId($result);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getDiff(AttributeMapInterface $map, array $diff): array
    {
        $result = [];
        foreach ($diff as $attribute => $update) {
            switch ($update['action']) {
                case AttributeMapInterface::ACTION_REPLACE:
                case AttributeMapInterface::ACTION_ADD:
                    $result[$attribute] = $update['value'];

                break;
                case AttributeMapInterface::ACTION_REMOVE:
                    $result[$attribute] = '';

                break;
                default:
                    throw new InvalidArgumentException('unknown diff action '.$update['action'].' given');
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function change(AttributeMapInterface $map, array $diff, array $object, EndpointObjectInterface $endpoint_object, bool $simulate = false): ?string
    {
        $url = $this->client->getConfig('base_uri').'/command/udm/put';
        $endpoint_object = $endpoint_object->getData();
        $dn = $this->getResourceId($object, $endpoint_object);
        $diff[self::ATTR_DN] = $endpoint_object[self::ATTR_DN];

        $this->logChange($dn, $diff);
        $map_parent = substr($dn, strpos($dn, ',') + 1);
        $ep_parent = substr($endpoint_object[self::ATTR_DN], strpos($endpoint_object[self::ATTR_DN], ',') + 1);

        if ($ep_parent !== $map_parent) {
            $this->moveUcsObject($endpoint_object[self::ATTR_DN], $map_parent, $simulate);
            $diff[self::ATTR_DN] = $this->getOne(['map' => $object])->getData()[self::ATTR_DN];
        }

        if ($simulate === false) {
            $result = $this->parse($this->client->post($url, $this->getRequestOptions([
                'json' => [
                    'options' => [
                        ['object' => $diff],
                    ],
                ],
            ])));

            $result = array_shift($result);
            $this->verifyWriteResult($result);

            if ($dn !== $diff[self::ATTR_DN]) {
                return $this->getOne(['map' => $object])->getData()[self::ATTR_DN];
            }

            return $dn;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function transformQuery(?array $query = null)
    {
        if ($this->filter_all !== null && empty($query)) {
            $filter_all = $this->getFilterAll();
            if ($filter_all === null || count($filter_all) > 1) {
                throw new UcsException\InvalidFilter('Tubee\\Endpoint\\Ucs only accepts a single atttribute filter (ucs restriction)');
            }

            return [
                'objectProperty' => key($filter_all),
                'objectPropertyValue' => reset($filter_all),
            ];
        }
        if (!empty($query)) {
            if ($this->filter_all !== null) {
                $this->logger->warning('Tubee\Endpoint\Ucs does not support combining queries, ignore filter_all configuration for this request', [
                    'category' => get_class($this),
                ]);
            }

            return [
                'objectProperty' => key($query),
                'objectPropertyValue' => reset($query),
            ];
        }

        return [
            'objectProperty' => 'None',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(?array $query = null): Generator
    {
        $url = $this->client->getConfig('base_uri').'/command/udm/query';

        $filter = $this->transformQuery($query);
        $this->logGetAll($filter);

        $filter['objectType'] = $this->flavor;
        $options = [
            'json' => [
                'options' => $filter,
            ],
        ];

        $i = 0;
        $body = $this->parse($this->client->post($url, $this->getRequestOptions($options)));

        foreach ($body as $object) {
            $dn = $this->getResourceId($object);
            yield $this->build($this->fetchObject($dn));
        }

        return $i;
    }

    /**
     * {@inheritdoc}
     */
    public function getOne(array $object, ?array $attributes = []): EndpointObjectInterface
    {
        $url = $this->client->getConfig('base_uri').'/command/udm/query';
        $query = $this->transformQuery($this->getFilterOne($object));
        $this->logGetOne($query);

        if (!isset($query['objectProperty']) || !isset($query['objectPropertyValue'])) {
            throw new UcsException\InvalidFilter('Either objectProperty or objectPropertyValue not set in filter_one');
        }

        $query['objectType'] = $this->flavor;
        $options = [
            'json' => [
                'options' => $query,
            ],
        ];

        $result = $this->parse($this->client->post($url, $this->getRequestOptions($options)));
        $filtered = [];

        //ucs can only do wildcard queries, it's required to manually filter the result
        foreach ($result as $resource) {
            if (isset($resource[$query['objectProperty']]) && $resource[$query['objectProperty']] === $query['objectPropertyValue']) {
                $filtered[] = $resource;
            }
        }

        if (count($filtered) > 1) {
            throw new Exception\ObjectMultipleFound('found more than one object with filter '.$this->filter_one);
        }
        if (count($filtered) === 0) {
            throw new Exception\ObjectNotFound('no object found with filter '.$this->filter_one);
        }

        $dn = $this->getResourceId(array_shift($filtered));

        return $this->build($this->fetchObject($dn));
    }

    /**
     * Get session id.
     */
    protected function getSessionId()
    {
        $jar = $this->client->getConfig('cookies')->toArray();
        foreach ($jar as $cookie) {
            if ($cookie['Name'] === self::SESSION_COOKIE_NAME) {
                return $cookie['Value'];
            }
        }

        throw new UcsException\SessionCookieNotAvailable('no session cookie '.self::SESSION_COOKIE_NAME.' found');
    }

    /**
     * Verify write result.
     */
    protected function verifyWriteResult(array $result): array
    {
        if (isset($result['details'])) {
            $message = $result['details'];
        } else {
            $message = 'write command failed with no further details provided';
        }

        if (isset($result['success'])) {
            if ($result['success'] === false) {
                throw new UcsException\RequestFailed($message);
            }

            return $result;
        }

        throw new UcsException\RequestFailed($message);
    }

    /**
     * Parse response.
     */
    protected function parse($response): array
    {
        $data = json_decode($response->getBody()->getContents(), true);

        $this->logger->debug('request to ['.$this->client->getConfig('base_uri').'] ended with code ['.$response->getStatusCode().']', [
            'category' => get_class($this),
        ]);

        if (!isset($data['result']) || !is_array($data['result'])) {
            throw new Exception\NotIterable('response body is invalid, iterable data expected');
        }

        return $data['result'];
    }

    /**
     * Get headers.
     */
    protected function getRequestOptions(array $options): array
    {
        return array_replace_recursive($options, [
            'json' => [
                'flavor' => $this->flavor,
            ],
            'headers' => [
                'Accept' => 'application/json',
                'X-Xsrf-Protection' => $this->session,
            ],
        ]);
    }

    /**
     * Get identifier.
     */
    protected function getResourceId(array $object, array $endpoint_object = []): ?string
    {
        if (isset($object[self::ATTR_DN])) {
            return $object[self::ATTR_DN];
        }

        if (isset($endpoint_object[self::ATTR_DN])) {
            return $endpoint_object[self::ATTR_DN];
        }

        throw new UcsException\NoEntryDn('no attribute $dn$ found in data object');
    }

    /**
     * Move ucs object.
     */
    protected function moveUcsObject(string $current_dn, string $container, bool $simulate = false): bool
    {
        $url = $this->client->getConfig('base_uri').'/command/udm/move';

        $this->logger->info('found ucs object ['.$current_dn.'] but is not at the expected place ['.$container.'], move object using udm/move ['.$url.']', [
            'category' => get_class($this),
        ]);

        if ($simulate === false) {
            $this->parse($this->client->post($url, $this->getRequestOptions([
                'json' => [
                    'options' => [
                        [
                            'options' => [
                                'container' => $container,
                            ],
                            'object' => $current_dn,
                        ],
                    ],
                ],
            ])));
        }

        return true;
    }

    /**
     * Request all object attributes.
     */
    protected function fetchObject(string $dn): array
    {
        $url = $this->client->getConfig('base_uri').'/command/udm/get';
        $this->logger->debug('fetch ucs object attributes from ['.$dn.'] on endpoint ['.$this->getIdentifier().'] using udm/get to ['.$url.']', [
            'category' => get_class($this),
        ]);

        $result = $this->parse($this->client->post($url, $this->getRequestOptions([
            'json' => [
                'options' => [
                    $dn,
                ],
            ],
        ])));

        return array_shift($result);
    }
}
