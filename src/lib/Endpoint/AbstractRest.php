<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2021 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use GuzzleHttp\Client;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\Rest\Exception as RestException;
use Tubee\EndpointObject\EndpointObjectInterface;
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
    protected $container = null;

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
        $response = $this->client->get('');

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function change(AttributeMapInterface $map, array $diff, array $object, EndpointObjectInterface $endpoint_object, bool $simulate = false): ?string
    {
        $uri = $this->client->getConfig('base_uri').'/'.$this->getResourceId($object, $endpoint_object);
        $this->logChange($uri, $diff);

        if ($simulate === false) {
            $this->client->patch($uri, [
                'json' => $diff,
            ]);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(AttributeMapInterface $map, array $object, EndpointObjectInterface $endpoint_object, bool $simulate = false): bool
    {
        $uri = $this->client->getConfig('base_uri').'/'.$this->getResourceId($object, $endpoint_object);
        $this->logDelete($uri);

        if ($simulate === false) {
            $response = $this->client->delete($uri);
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
            $result = $this->client->post('', [
                'json' => $object,
            ]);

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
     * Decode response.
     */
    protected function decodeResponse($response)
    {
        $this->logger->debug('request to ['.$this->client->getConfig('base_uri').'] ended with code ['.$response->getStatusCode().']', [
            'category' => get_class($this),
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Verify response.
     */
    protected function getResponse($response): array
    {
        $data = $this->decodeResponse($response);

        if (isset($this->container) && $this->container !== null) {
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
     * Get identifier.
     */
    protected function getResourceId(array $object, ?EndpointObjectInterface $endpoint_object = null): string
    {
        if (isset($object[$this->identifier])) {
            return $object[$this->identifier];
        }

        if ($endpoint_object !== null) {
            $data = $endpoint_object->getData();
            if (isset($data[$this->identifier])) {
                return $data[$this->identifier];
            }
        }

        throw new RestException\IdNotFound('attribute '.$this->identifier.' is not available');
    }
}
