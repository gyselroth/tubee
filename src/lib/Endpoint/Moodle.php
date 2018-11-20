<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use Generator;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\DataType\DataTypeInterface;
use Tubee\Endpoint\Moodle\ApiClient;
use Tubee\Endpoint\Moodle\Exception as MoodleEndpointException;
use Tubee\EndpointObject\EndpointObjectInterface;
use Tubee\Workflow\Factory as WorkflowFactory;

class Moodle extends AbstractEndpoint
{
    /**
     * Moodle.
     *
     * @var ApiClient
     */
    protected $moodle;

    /**
     * Call map.
     *
     * @var array
     */
    protected $call_map = [];

    /**
     * Init endpoint.
     */
    public function __construct(string $name, string $type, array $call_map, ApiClient $wrapper, DataTypeInterface $datatype, WorkflowFactory $workflow, LoggerInterface $logger, array $resource = [])
    {
        $this->moodle = $wrapper;
        $this->call_map = $call_map;
        parent::__construct($name, $type, $datatype, $workflow, $logger, $resource);
    }

    /**
     * {@inheritdoc}
     */
    /*public function getDiff(AttributeMapInterface $map, array $object, array $endpoint_object): array
    {
        $id = $this->getEndpointId($endpoint_object);
        $diff = [[]];
        $diff[0]['id'] = $id;

        foreach ($map->getMap() as $attr => $value) {
            if (is_array($value)) {
                throw new Exception\EndpointCanNotHandleArray('endpoint can not handle arrays ["'.$value.'"], did you forget to set a decorator?');
            }

            if (isset($value['ensure'])) {
                $attr_name = strtolower($attr);
                $isset_on_moodle = isset($endpoint_object[$attr_name]);

                if ($value['ensure'] === 'exists' && isset($endpoint_object[$attr_name]) && $endpoint_object[$attr_name] !== '') {
                    continue;
                }
                if (($value['ensure'] === 'last' || $value['ensure'] === 'exists') && isset($object[$attr])) {
                    if ($isset_on_moodle && $object[$attr] === $endpoint_object[$attr_name]) {
                        continue;
                    }

                    $diff[0][$attr] = $object[$attr];
                } elseif ($value['ensure'] === 'absent') {
                    $diff[0][$attr] = '';
                }
            }
        }

        return $diff;
    }*/

    /**
     * {@inheritdoc}
     */
    public function getDiff(AttributeMapInterface $map, array $diff): array
    {
        $result = [];
        foreach ($diff as $attribute => $update) {
            switch ($update['action']) {
                case AttributeMapInterface::ACTION_REPLACE:
                    $result[$attribute] = $update['value'];

                break;
                case AttributeMapInterface::ACTION_REMOVE:
                    $result[$attribute] = '';

                break;
                /*case AttributeMapInterface::ACTION_ADD:
                    $result['$addToSet'][$attribute] = $update['value'];
                break;*/
                default:
                    throw new InvalidArgumentException('unknown diff action '.$update['action'].' given');
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function change(AttributeMapInterface $map, array $diff, array $object, array $endpoint_object, bool $simulate = false): ?string
    {
        /*$id = $this->getEndpointId($endpoint_object);
        $diff = [$diff];
        $diff[0]['id'] = $id;

        $this->logger->info('update moodle object ['.$id.'] on endpoint ['.$this->getEndpointIdentifier().']', [
            'category' => get_class($this),
        ]);

        if ($simulate === false) {
            $identifier = isset(self::METHODS[$this->resource_type]['change']['identifier']) ? self::METHODS[$this->resource_type]['change']['identifier'] : '';
            $diff = http_build_query([$identifier => $diff]);

            $this->moodle->restCall($diff, self::METHODS[$this->resource_type]['change']['function']);
        }

        return null;*/
    }

    /**
     * {@inheritdoc}
     */
    public function create(AttributeMapInterface $map, array $object, bool $simulate = false): ?string
    {
        /*foreach ($object as $key => $value) {
            if (is_array($value)) {
                throw new Exception\EndpointCanNotHandleArray('endpoint can not handle arrays ["'.$key.'"], did you forget to set a decorator?');
            }
        }

        $identifier = isset(self::METHODS[$this->resource_type]['create']['identifier']) ? self::METHODS[$this->resource_type]['create']['identifier'] : '';
        $prepared_data = http_build_query([$identifier => [0 => $object]]);

        $this->logger->info('create new moodle object on endpoint ['.$this->getEndpointIdentifier().'] with attributes [{attributes}]', [
            'category' => get_class($this),
            'attributes' => $object,
        ]);

        if ($simulate === false) {
            $this->moodle->restCall($prepared_data, self::METHODS[$this->resource_type]['create']['function']);
        }

        return $identifier;*/
    }

    /**
     * {@inheritdoc}
     */
    public function delete(AttributeMapInterface $map, array $object, array $endpoint_object, bool $simulate = false): bool
    {
        /*$id = $this->getEndpointId($endpoint_object);
        $diff = [[]];
        $diff[0] = $id;

        $this->logger->info('delete existing moodle object ['.$id.'] on endpoint ['.$this->getEndpointIdentifier().']', [
            'category' => get_class($this),
        ]);

        if ($simulate === false) {
            $identifier = isset(self::METHODS[$this->resource_type]['delete']['identifier']) ? self::METHODS[$this->resource_type]['delete']['identifier'] : '';
            $diff = http_build_query([$identifier => $diff]);

            $this->moodle->restCall($diff, self::METHODS[$this->resource_type]['delete']['function']);
        }

        return true;*/
    }

    /**
     * {@inheritdoc}
     */
    public function transformQuery(?array $query = null)
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(?array $query = []): Generator
    {
        /*$this->logger->debug('find all moodle objects with moodle filter ['.$this->filter_all.'] on endpoint ['.$this->getEndpointIdentifier().']', [
            'category' => get_class($this),
        ]);

        $result = $this->moodle->restCall('&'.$this->filter_all, self::METHODS[$this->resource_type]['get_all']['function']);

        foreach ($result as $object) {
            yield $object;
        }*/
    }

    /**
     * {@inheritdoc}
     */
    public function getOne(array $object, array $atitributes = []): EndpointObjectInterface
    {
        /*$filter = $this->getFilterOne($object);

        $this->logger->debug('find moodle object with moodle filter ['.$filter.'] on endpoint ['.$this->getEndpointIdentifier().']', [
            'category' => get_class($this),
        ]);

        $result = $this->moodle->restCall('&'.$filter, self::METHODS[$this->resource_type]['get']['function']);

        if (count($result) > 1) {
            throw new Exception\ObjectMultipleFound('found more than one object with filter '.$filter);
        }
        if (count($result) === 0) {
            throw new Exception\ObjectNotFound('no object found with filter '.$filter);
        }

        return (array) array_shift($result);*/
    }

    /**
     * Get moodle resource id.
     */
    protected function getEndpointId(array $endpoint_object): string
    {
        if (isset($endpoint_object['id'])) {
            return $endpoint_object['id'];
        }

        throw new MoodleEndpointException\NoResourceId('no attribute id found in data object');
    }
}
