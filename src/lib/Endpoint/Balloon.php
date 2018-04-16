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
use Tubee\Endpoint\Balloon\ApiClient;

class Balloon extends AbstractEndpoint
{
    /**
     * Balloon methods.
     */
    const METHODS = [
        'node' => [
            'get' => [
                'function' => '/node/attributes?p=',
            ],
            'create' => [
                'function' => '/collection/?p=',
            ],
            'delete' => [
                'function' => '/node?id=',
                'method' => 'delete',
            ],
        ],
    ];

    /**
     * Balloon.
     *
     * @var Balloon
     */
    protected $balloon;

    /**
     * Type.
     *
     * @var string
     */
    protected $resource_type = 'node';

    /**
     * Path.
     *
     * @var string
     */
    protected $path = '';

    /**
     * Init endpoint.
     *
     * @param string            $name
     * @param string            $type
     * @param ApiClient         $wrapper
     * @param DataTypeInterface $datatype
     * @param Logger            $logger
     * @param iterable          $config
     */
    public function __construct(string $name, string $type, string $resource_type, ApiClient $wrapper, DataTypeInterface $datatype, LoggerInterface $logger, ?Iterable $config = null)
    {
        if (!isset(self::METHODS[$resource_type])) {
            throw new InvalidArgumentException('balloon resource type ['.$resource_type.'] does not exists');
        }

        $this->balloon = $wrapper;
        $this->resource_type = $resource_type;
        parent::__construct($name, $type, $datatype, $logger, $config);
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
                    $result[$attribute] = $update['value'];

                break;
                case AttributeMapInterface::ACTION_REMOVE:
                    $result[$attribute] = null;

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
    public function change(AttributeMapInterface $map, Iterable $diff, Iterable $object, Iterable $endpoint_object, bool $simulate = false): string
    {
        $id = $this->getId($object, $endpoint_object);

        $this->logger->info('update balloon node ['.$id.']', [
            'category' => get_class($this),
        ]);

        if ($simulate === false) {
            $this->balloon->restCall($value, str_replace('{id}', $source_id, self::FIELD_FUNCTIONS[$key]['function']).'=', self::FIELD_FUNCTIONS[$key]['method']);
        }

        return $id;
    }

    /**
     * {@inheritdoc}
     */
    public function create(AttributeMapInterface $map, Iterable $object, bool $simulate = false): ?string
    {
        foreach ($object as $key => $value) {
            if (is_array($value)) {
                throw new Exception\EndpointCanNotHandleArray('endpoint can not handle arrays ["'.$key.'"], did you forget to set a decorator?');
            }
        }

        $this->logger->info('create new balloon node on endpoint ['.$this->name.'] with attributes [{attributes}]', [
            'category' => get_class($this),
            'attributes' => $object,
        ]);

        if ($simulate === false) {
            $result = $this->balloon->restCall($value, self::METHODS[$this->resource_type]['create']['function'].$this->path, self::FIELD_FUNCTIONS[$key]['method']);

            return $result['id'];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(AttributeMapInterface $map, Iterable $object, Iterable $endpoint_object, bool $simulate = false): bool
    {
        $id = $this->getId($object, $endpoint_object);

        $this->logger->info('delete existing balloon object ['.$id.'] on endpoint ['.$this->name.']', [
            'category' => get_class($this),
        ]);

        if ($simulate === false) {
            $this->balloon->restCall($id, self::METHODS[$this->resource_type]['delete']['function'], self::METHODS[$this->resource_type]['delete']['method']);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getAll($filter): Generator
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getOne(Iterable $object, Iterable $attributes = []): Iterable
    {
        $filter = $this->getFilterOne($object);

        $this->logger->debug('find balloon node with filter ['.$filter.'] on endpoint ['.$this->name.']', [
            'category' => get_class($this),
        ]);

        $result = $this->balloon->restCall($this->path.$filter, self::METHODS[$this->resource_type]['get']['function']);

        if (count($result) > 1) {
            throw new Exception\ObjectMultipleFound('found more than one object with filter '.$filter);
        }
        if (count($result) === 0) {
            throw new Exception\ObjectNotFound('no object found with filter '.$filter);
        }

        return (array) array_shift($result);
    }

    /**
     * Get Id.
     *
     * @return string
     */
    protected function getId(Iterable $object, Iterable $endpoint_object = []): string
    {
        if (isset($object['id'])) {
            return $object['id'];
        }
        if (isset($endpoint_object['id'])) {
            return $endpoint_object['id'];
        }

        throw new BalloonException\NoResourceId('no attribute id found in data object');
    }
}
