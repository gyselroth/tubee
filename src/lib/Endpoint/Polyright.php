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

    /**
     * If archive attr is set as an attribute in worfklow, the object gets archived on the endpoint.
     */
    public const ARCHIVE_ATTR = 'archive';

    /**
     * Included person information.
     */
    public const ADDITIONAL_PERSON_INFORMATION = [
        'includePersonalInformation',
        'includeValidity',
        'includeEmployeeData',
        'includeStudentData',
        'includeDefaultMedium',
        'includeDefaultPersonalAccount',
        'includeRepresentedPersons',
        'includeRepresentativePersons',
        'includeCustomFields',
        'includeComputedFields',
        'includePricingProfileData',
    ];

    /**
     * Divider for additional attributes.
     */
    public const ADDITIONAL_ATTR_DIVIDER = ':';

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

            foreach ($query as $key => $value) {
                $return[$key] = $value;
            }

            return $return;
        }

        return null;
    }

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
            $matches = $this->matchItemsByQuery($data, $query);

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
        $filter = $this->transformQuery($this->getFilterOne($object));
        $this->logGetOne($filter);

        $attributes = implode('=true&', self::ADDITIONAL_PERSON_INFORMATION).'=true';

        try {
            $result = $this->client->get($this->client->getConfig('base_uri').'/'.reset($filter).'?'.$attributes);
            $data = $this->getResponse($result);

            if (isset($data[$this->identifier])) {
                $data[$this->identifier] = strval($data[$this->identifier]);
            }
        } catch (RequestException $e) {
            if ($e->getCode() === 404) {
                throw new Exception\ObjectNotFound('no object found with filter '.json_encode($filter));
            }

            throw $e;
        }

        return $this->build($this->flattenArray($data), $filter);
    }

    /**
     * {@inheritdoc}
     */
    public function create(AttributeMapInterface $map, array $object, bool $simulate = false): ?string
    {
        $this->unflattenArray($object);

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
    public function change(AttributeMapInterface $map, array $diff, array $object, EndpointObjectInterface $endpoint_object, bool $simulate = false): ?string
    {
        if (isset($diff[self::ARCHIVE_ATTR])) {
            if ($diff[self::ARCHIVE_ATTR]) {
                if ($endpoint_object->getData()['status'] === 'Archived') {
                    $this->logger->debug('object on endpoint [{identifier}] is already up2date', [
                        'identifier' => $this->getIdentifier(),
                        'category' => get_class($this),
                    ]);
                } else {
                    $this->archive($diff, $object, $endpoint_object, $simulate);
                }
            } else if ($endpoint_object->getData()['status'] === 'Archived'){
                $this->unarchive($diff, $object, $endpoint_object, $simulate);
            } else {
                unset($diff[self::ARCHIVE_ATTR]);

                if ($diff === []) {
                    $this->logger->debug('object on endpoint [{identifier}] is already up2date', [
                        'identifier' => $this->getIdentifier(),
                        'category' => get_class($this),
                    ]);

                    return null;
                }
            }
        }

        $uri = $this->client->getConfig('base_uri').'/'.$this->getResourceId($object, $endpoint_object);
        $diff = $this->unflattenArray($diff);
        $this->logChange($uri, $diff);

        if ($simulate === false) {
            $this->client->patch($uri, [
                'json' => $diff,
            ]);
        }

        return null;
    }

    protected function archive(array $diff, array $object, EndpointObjectInterface $endpoint_object, bool $simulate): void
    {
        $uri = $this->client->getConfig('base_uri').'/'.$this->getResourceId($object, $endpoint_object).'/archive';

        $this->logger->info('archive polyright object [{object}] on endpoint [{identifier}]', [
            'category' => get_class($this),
            'identifier' => $this->getIdentifier(),
            'object' => $diff,
        ]);

        if ($simulate === false) {
            $this->client->post($uri);
        }
    }

    protected function unarchive(array $diff, array $object, EndpointObjectInterface $endpoint_object, bool $simulate): void
    {
        $uri = $this->client->getConfig('base_uri').'/'.$this->getResourceId($object, $endpoint_object).'/unarchive';

        $this->logger->info('unarchive polyright object [{object}] on endpoint [{identifier}]', [
            'category' => get_class($this),
            'identifier' => $this->getIdentifier(),
            'object' => $diff,
        ]);

        if ($simulate === false) {
            $this->client->post($uri);
        }
    }

    protected function matchItemsByQuery(array $data, ?array $query): array
    {
        return array_filter(array_shift($data), function ($item) use ($query) {
            foreach ($query as $key => $value) {
                if (!isset($item[$key]) || $item[$key] != $value) {
                    return false;
                }
            }

            return true;
        });
    }

    protected function flattenArray(array $data, string $prefix = ''): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $newKey = $prefix ? $prefix.self::ADDITIONAL_ATTR_DIVIDER.$key : $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    protected function unflattenArray(array $array): array
    {
        foreach ($array as $attr => $value) {
            if (str_contains($attr, self::ADDITIONAL_ATTR_DIVIDER)) {
                $container = explode(':', $attr);
                $array[$container[0]][$container[1]] = $value;
                unset($array[$attr]);
            }
        }

        return $array;
    }
}
