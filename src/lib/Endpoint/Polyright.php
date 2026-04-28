<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2026 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Jenssegers\ImageHash\ImageHash;
use Jenssegers\ImageHash\Implementations\AverageHash;
use MongoDB\BSON\Binary;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\Collection\CollectionInterface;
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
     * @var ImageHash
     */
    protected $hasher;

    /**
     * Init endpoint.
     */
    public function __construct(string $name, string $type, Client $client, CollectionInterface $collection, WorkflowFactory $workflow, LoggerInterface $logger, array $resource = [])
    {
        $this->logger = $logger;
        $this->hasher = new ImageHash(new AverageHash());
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
        $object = $this->unflattenArray($object);

        list($object, $photoAttr) = $this->checkPhotoAttr($object);

        if ($simulate === false) {
            $result = $this->client->post('', [
                'json' => $object,
            ]);

            $resourceId = $this->getResourceId(json_decode($result->getBody()->getContents(), true));

            if ($photoAttr !== []) {
                $this->photoUpload($photoAttr, $resourceId);
            }

            return $resourceId;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function change(AttributeMapInterface $map, array $diff, array $object, EndpointObjectInterface $endpoint_object, bool $simulate = false): ?string
    {
        $resourceId = $this->getResourceId($object, $endpoint_object);
        list($diff, $photoAttr) = $this->checkPhotoAttr($diff, true, $resourceId);

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
            } elseif ($endpoint_object->getData()['status'] === 'Archived') {
                $this->unarchive($diff, $object, $endpoint_object, $simulate);
            } else {
                unset($diff[self::ARCHIVE_ATTR]);

                if ($diff === [] && $photoAttr === []) {
                    $this->logger->debug('object on endpoint [{identifier}] is already up2date', [
                        'identifier' => $this->getIdentifier(),
                        'category' => get_class($this),
                    ]);

                    return null;
                }
            }
        }

        $uri = $this->client->getConfig('base_uri').'/'.$resourceId;
        $diff = $this->unflattenArray($diff);
        $this->logChange($uri, $diff);

        if ($simulate === false) {
            if ($diff !== []) {
                $this->client->patch($uri, [
                    'json' => $diff,
                ]);
            }

            if ($photoAttr !== []) {
                $this->photoUpload($photoAttr, $resourceId);
            }
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

    protected function checkPhotoAttr(array $object, bool $compare = false, string $resourceId = ''): array
    {
        $photoAttr = [];

        foreach ($object as $attr => $value) {
            if ($value instanceof Binary) {
                $this->logger->debug('there is a photo attribute [{attr}]', [
                    'attr' => $attr,
                    'category' => get_class($this),
                ]);

                $photoAttr = $value;
                unset($object[$attr]);
            }
        }

        if ($photoAttr !== [] && $compare && $resourceId !== '') {
            if (!$this->samePhoto($photoAttr, $resourceId)) {
                return [$object, $photoAttr];
            }

            return [$object, []];
        }

        return [$object, $photoAttr];
    }

    protected function samePhoto(Binary $photo, string $resourceId): bool
    {
        $uri = $this->client->getConfig('base_uri').'/'.$resourceId.'/photo';

        $this->logger->debug('compare photo for resource [{resource}] on endpoint [{identifier}]', [
            'category' => get_class($this),
            'identifier' => $this->getIdentifier(),
            'resource' => $uri,
        ]);

        $result = $this->client->get($uri);

        $this->logger->debug('request to ['.$uri.'] ended with code ['.$result->getStatusCode().']', [
            'category' => get_class($this),
        ]);

        if ($result->getStatusCode() !== 200) {
            return false;
        }

        $endpointPhoto = $result->getBody()->getContents();
        $endpointPhotoHash = $this->hasher->hash($endpointPhoto);
        $sourcePhotoHash = $this->hasher->hash($photo->getData());

        if ($this->hasher->distance($endpointPhotoHash, $sourcePhotoHash) < 5) {
            $this->logger->debug('photos are similar - do not upload new photo to endpoint [{identifier}]', [
                'identifier' => $this->getIdentifier(),
                'category' => get_class($this),
            ]);

            return true;
        }

        $this->logger->debug('there is a new photo - upload new photo to endpoint [{identifier}]', [
            'identifier' => $this->getIdentifier(),
            'category' => get_class($this),
        ]);

        return false;
    }

    protected function photoUpload(Binary $attr, string $resourceId): void
    {
        $uri = $this->client->getConfig('base_uri').'/'.$resourceId.'/photo';

        $this->logger->debug('upload photo for resource [{resource}] on endpoint [{identifier}]', [
            'category' => get_class($this),
            'identifier' => $this->getIdentifier(),
            'resource' => $uri,
        ]);

        $this->client->put($uri, [
            'multipart' => [
                [
                    'name' => 'photo_'.$resourceId,
                    'contents' => $attr,
                ],
            ],
        ]);
    }
}
