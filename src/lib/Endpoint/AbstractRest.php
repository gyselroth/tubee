<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use GuzzleHttp\Client;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\DataType\DataTypeInterface;
use Tubee\Endpoint\Rest\Exception as RestException;
use Tubee\Workflow\Factory as WorkflowFactory;

abstract class AbstractRest extends AbstractEndpoint
{
    /**
     * Guzzle client.
     *
     * @var Client
     */
    protected $client;

    /**
     * Request options.
     *
     * @var array
     */
    protected $request_options = [];

    /**
     * Container.
     *
     * @var string
     */
    protected $container;

    /**
     * Access token.
     *
     * @var string
     */
    protected $access_token;

    /**
     * Update put vs patch.
     *
     * @var string
     */
    protected $update_method = 'PATCH';

    /**
     * ID field.
     *
     * @var string
     */
    protected $id_field = 'id';

    /**
     * Init endpoint.
     */
    public function __construct(string $name, string $type, Client $client, DataTypeInterface $datatype, WorkflowFactory $workflow, LoggerInterface $logger, array $resource = [])
    {
        $this->client = $client;

        if (isset($resource['data']['resource']['rest_options'])) {
            $this->setRestOptions($resource['data']['resource']['rest_options']);
        }

        parent::__construct($name, $type, $datatype, $workflow, $logger, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function setup(bool $simulate = false): EndpointInterface
    {
        if (isset($this->resource['data']['resource']['auth']) && $this->resource['data']['resource']['auth'] === 'oauth2') {
            $oauth = $this->resource['data']['resource']['oauth2'];

            $this->logger->debug('fetch access_token from ['.$oauth['token_endpoint'].']', [
                'category' => get_class($this),
            ]);

            $client = new Client();
            $response = $client->post($oauth['token_endpoint'], [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $oauth['client_id'],
                    'client_secret' => $oauth['client_secret'],
                    'scope' => $oauth['scope'],
                ],
            ]);

            $this->logger->debug('fetch access_token ended with status ['.$response->getStatusCode().']', [
                'category' => get_class($this),
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if (isset($body['access_token'])) {
                $this->access_token = $body['access_token'];
            } else {
                throw new RestException\AccessTokenNotAvailable('No access_token in token_endpoint response');
            }
        }

        $response = $this->client->get('', $this->getRequestOptions());

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setRestOptions(?array $config = null): EndpointInterface
    {
        if ($config === null) {
            return $this;
        }

        foreach ($config as $option => $value) {
            switch ($option) {
                case 'id_field':
                case 'container':
                case 'update_method':
                    $this->{$option} = (string) $value;

                    break;
                case 'request_options':
                    $this->request_options = $value;

                break;
                default:
                    throw new InvalidArgumentException('unknown rest option '.$option.' given');
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown(bool $simulate = false): EndpointInterface
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(AttributeMapInterface $map, array $object, array $endpoint_object, bool $simulate = false): bool
    {
        $this->logger->info('delete object from endpoint ['.$this->getIdentifier().'] using DELETE to ['.$this->client->getConfig('base_uri').'/'.$this->getResourceId($endpoint_object).']', [
            'category' => get_class($this),
        ]);

        if ($simulate === false) {
            $response = $this->client->delete('/'.$this->getResourceId($endpoint_object));
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function create(AttributeMapInterface $map, array $object, bool $simulate = false): ?string
    {
        $this->logger->info('create new object on endpoint ['.$this->getIdentifier().'] using POST to ['.$this->client->getConfig('base_uri').']', [
            'category' => get_class($this),
        ]);

        if ($simulate === false) {
            $result = $this->client->post('', [
                'json' => $object,
            ]);

            $body = json_decode($result->getBody()->getContents(), true);

            return $this->getResourceId($body);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDiff(AttributeMapInterface $map, array $diff): array
    {
        return $diff;
        $result = [];
        foreach ($diff as $attribute => $update) {
            switch ($update['action']) {
                case AttributeMapInterface::ACTION_REPLACE:
                case AttributeMapInterface::ACTION_ADD:
                    $result[$attribute] = $update['value'];

                break;
                case AttributeMapInterface::ACTION_REMOVE:
                    $result[$attribute] = null;

                break;
                default:
                    throw new InvalidArgumentException('unknown diff action '.$update['action'].' given');
            }
        }

        return $result;
    }

    /**
     * Verify response.
     */
    protected function getResponse($response): array
    {
        $data = json_decode($response->getBody()->getContents(), true);

        $this->logger->debug('request to ['.$this->client->getConfig('base_uri').'] ended with code ['.$response->getStatusCode().']', [
            'category' => get_class($this),
        ]);

        if (isset($this->container)) {
            if (isset($data[$this->container])) {
                $data = $data[$this->container];
            } else {
                throw new RestException\InvalidContainer('specified container '.$this->container.' does not exists in response');
            }
        }

        if (!is_array($data)) {
            throw new RestException\NotIterable('response is not iterable');
        }

        return $data;
    }

    /**
     * Get headers.
     */
    protected function getRequestOptions(): array
    {
        $options = $this->request_options;

        if ($this->access_token) {
            return array_merge($options, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => "Bearer {$this->access_token}",
                ],
            ]);
        }

        return [];
    }

    /**
     * Get identifier.
     */
    protected function getResourceId(array $object): ?string
    {
        if (isset($object[$this->id_field])) {
            return $object[$this->id_field];
        }
    }
}
