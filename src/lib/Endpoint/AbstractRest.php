<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use GuzzleHttp\Client;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\Rest\Exception as RestException;
use Tubee\Workflow\Factory as WorkflowFactory;

abstract class AbstractRest extends AbstractEndpoint
{
    use LoggerTrait;

    /**
     * Guzzle client.
     *
     * @var Client
     */
    protected $client;

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
     * Init endpoint.
     */
    public function __construct(string $name, string $type, Client $client, CollectionInterface $collection, WorkflowFactory $workflow, LoggerInterface $logger, array $resource = [])
    {
        $this->client = $client;
        parent::__construct($name, $type, $collection, $workflow, $logger, $resource);
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

            $response = $this->client->post($oauth['token_endpoint'], [
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
    public function change(AttributeMapInterface $map, array $diff, array $object, array $endpoint_object, bool $simulate = false): ?string
    {
        $uri = $this->client->getConfig('base_uri').'/'.$this->getResourceId($object, $endpoint_object);
        $this->logChange($uri, $diff);

        if ($simulate === false) {
            $result = $this->client->patch($uri, $this->getRequestOptions([
                'json' => $diff,
            ]));
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(AttributeMapInterface $map, array $object, array $endpoint_object, bool $simulate = false): bool
    {
        $uri = $this->client->getConfig('base_uri').'/'.$this->getResourceId($object, $endpoint_object);
        $this->logDelete($uri);

        if ($simulate === false) {
            $response = $this->client->delete($uri, $this->getRequestOptions());
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function create(AttributeMapInterface $map, array $object, bool $simulate = false): ?string
    {
        $this->logCreate($object);

        if ($simulate === false) {
            $result = $this->client->post('', $this->getRequestOptions([
                'json' => $object,
            ]));

            $body = json_decode($result->getBody()->getContents(), true);

            return $this->getResourceId($body);
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
            throw new Exception\NotIterable('response is not iterable');
        }

        return $data;
    }

    /**
     * Get headers.
     */
    protected function getRequestOptions(array $options = []): array
    {
        if ($this->access_token) {
            return array_merge($options, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => "Bearer {$this->access_token}",
                ],
            ]);
        }

        return $options;
    }

    /**
     * Get identifier.
     */
    protected function getResourceId(array $object, array $endpoint_object = []): string
    {
        if (isset($object[$this->identifier])) {
            return $object[$this->identifier];
        }

        if (isset($endpoint_object[$this->identifier])) {
            return $endpoint_object[$this->identifier];
        }

        throw new RestException\IdNotFound('attribute '.$this->identifier.' is not available');
    }
}
