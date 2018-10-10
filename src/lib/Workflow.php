<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use InvalidArgumentException;
use MongoDB\BSON\UTCDateTime;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\DataObject\DataObjectInterface;
use Tubee\DataObject\Exception as DataObjectException;
use Tubee\DataType\DataTypeInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Endpoint\Exception as EndpointException;
use Tubee\Resource\AbstractResource;
use Tubee\Resource\AttributeResolver;
use Tubee\Workflow\Exception;
use Tubee\Workflow\WorkflowInterface;

class Workflow extends AbstractResource implements WorkflowInterface
{
    /**
     * Workflow name.
     *
     * @var string
     */
    protected $name;

    /**
     * Endpoint.
     *
     * @var EndpointInterface
     */
    protected $endpoint;

    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Attribute map.
     *
     * @var AttributeMap
     */
    protected $attribute_map;

    /**
     * Condition.
     *
     * @var string
     */
    protected $ensure = WorkflowInterface::ENSURE_EXISTS;

    /**
     *  Condiditon.
     */
    protected $condition;

    /**
     * Expression.
     *
     * @var ExpressionLanguage
     */
    protected $expression;

    /**
     * Initialize.
     */
    public function __construct(string $name, string $ensure, ExpressionLanguage $expression, AttributeMapInterface $attribute_map, EndpointInterface $endpoint, LoggerInterface $logger, array $resource = [])
    {
        $this->name = $name;
        $this->expression = $expression;
        $this->attribute_map = $attribute_map;
        $this->endpoint = $endpoint;
        $this->logger = $logger;
        $this->resource = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): string
    {
        return $this->endpoint->getIdentifier().'::'.$this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(ServerRequestInterface $request): array
    {
        $resource = [
            '_links' => [
                'self' => ['href' => (string) $request->getUri()],
            ],
            'kind' => 'Workflow',
            'name' => $this->name,
            'ensure' => $this->ensure,
            'condition' => $this->condition,
            'map' => $this->attribute_map->getMap(),
        ];

        return AttributeResolver::resolve($request, $this, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function getEndpoint(): EndpointInterface
    {
        return $this->endpoint;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeMap(): AttributeMapInterface
    {
        return $this->attribute_map;
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup(DataObjectInterface $object, UTCDateTime $ts, bool $simulate = false): bool
    {
        $attributes = $object->toArray();
        if ($this->checkCondition($attributes, true) === false) {
            return false;
        }

        if (count($object->getEndpoints()) === 0) {
            $this->logger->debug('object has never been touched, no _source exists, current garbage collector workflow ['.$this->getIdentifier().'] will match', [
                'category' => get_class($this),
            ]);
        }

        $map = $this->attribute_map->map($attributes, $ts);
        $this->logger->debug('mapped object attributes [{map}] for cleanup', [
            'category' => get_class($this),
            'map' => array_keys($map),
        ]);

        $filter = array_intersect_key($map, array_flip($this->endpoint->getImport()));

        switch ($this->ensure) {
            case WorkflowInterface::ENSURE_ABSENT:
                return $this->endpoint->getDataType()->deleteObject($object->getId(), $simulate);

            break;
            case WorkflowInterface::ENSURE_DISABLED:
                return $this->endpoint->getDataType()->disable($object->getId(), $simulate);

            break;
            case WorkflowInterface::ENSURE_EXISTS:
                return true;

            break;
            case WorkflowInterface::ENSURE_LAST:
                //$object_ts = new UTCDateTime();
                //$operation = $this->getMongoDBOperation($map, $object, $object_ts);
                //$this->endpoint->getDataType()->change(['_id' => $object['id']], $operation, $simulate);

                return true;

            break;
            default:
                throw new InvalidArgumentException('invalid value for ensure in workflow given, only absent, disabled, exists or last is allowed');
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function import(DataTypeInterface $datatype, Iterable $object, UTCDateTime $ts, bool $simulate = false): bool
    {
        if ($this->checkCondition($object, false) === false) {
            return false;
        }

        $map = $this->attribute_map->map($object, $ts);
        $this->logger->debug('mapped object attributes [{map}] for import', [
            'category' => get_class($this),
            'map' => array_keys($map),
        ]);

        $exists = $this->getImportObject($datatype, $map, $ts);
        $object_ts = new UTCDateTime();

        if ($exists === null && $this->ensure !== WorkflowInterface::ENSURE_EXISTS || $exists !== null && $this->ensure === WorkflowInterface::ENSURE_EXISTS) {
            return false;
        }

        switch ($this->ensure) {
            case WorkflowInterface::ENSURE_ABSENT:
                $datatype->deleteObject($exists->getId(), $simulate);

                return true;

            break;
            case WorkflowInterface::ENSURE_DISABLED:
                $datatype->disable($exists->getId(), $simulate);
                $this->importRelations($exists, $map);

                return true;

            break;
            case WorkflowInterface::ENSURE_EXISTS:
                $endpoints = [
                    $this->endpoint->getName() => [
                        'last_sync' => $object_ts,
                    ],
                ];

                $id = $datatype->createObject(Helper::pathArrayToAssociative($map), $simulate, $endpoints);
                $this->importRelations($datatype->getObject(['_id' => $id]), $map);

                return true;

            break;
            case WorkflowInterface::ENSURE_LAST:
                $object = $this->map($map, $exists->getData(), $object_ts);

                $endoints = [];
                $endpoints['endpoints.'.$this->endpoint->getName().'.last_sync'] = $object_ts;
                $datatype->changeObject($exists, $object, $simulate, $endpoints);
                $this->importRelations($exists, $map);

                return true;

            break;
            default:
                throw new InvalidArgumentException('invalid value for ensure in workflow given, only absent, disabled, exists or last is allowed');
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function export(DataObjectInterface $object, UTCDateTime $ts, bool $simulate = false): bool
    {
        $attributes = $object->toArray();
        if ($this->checkCondition($attributes, false) === false) {
            return false;
        }

        $map = $this->attribute_map->map($attributes, $ts);
        $this->logger->debug('mapped object attributes [{map}] for write', [
            'category' => get_class($this),
            'map' => array_keys($map),
        ]);

        $exists = $this->getExportObject($map);

        if ($exists === false && $this->ensure !== WorkflowInterface::ENSURE_EXISTS || $exists !== false && $this->ensure === WorkflowInterface::ENSURE_EXISTS) {
            return false;
        }

        switch ($this->ensure) {
            case WorkflowInterface::ENSURE_ABSENT:
                $this->logger->info('delete existing object from endpoint ['.$this->endpoint->getName().']', [
                    'category' => get_class($this),
                ]);

                $this->endpoint->delete($this->attribute_map, $map, $exists, $simulate);

                return true;

            break;
            case WorkflowInterface::ENSURE_EXISTS:
                $this->logger->info('create new object on endpoint ['.$this->endpoint->getName().']', [
                    'category' => get_class($this),
                ]);

                $result = $this->endpoint->create($this->attribute_map, $map, $simulate);

                $endpoints = [];
                $endpoints['endpoints.'.$this->endpoint->getName()]['last_sync'] = new UTCDateTime();

                if ($result !== null) {
                    $endpoints['endpoints.'.$this->endpoint->getName()]['id'] = $result;
                }

                $this->endpoint->getDataType()->changeObject($object, $object->getData(), $simulate, $endpoints);

                return true;

            break;
            case WorkflowInterface::ENSURE_LAST:
                $this->logger->info('change object on endpoint ['.$this->endpoint->getName().']', [
                    'category' => get_class($this),
                ]);

                $diff = $this->attribute_map->getDiff($map, $exists);
                $endpoints = [];
                if (count($diff) > 0) {
                    $this->logger->info('update object on endpoint ['.$this->endpoint->getIdentifier().'] with attributes [{attributes}]', [
                        'category' => get_class($this),
                        'attributes' => $diff,
                    ]);

                    $diff = $this->endpoint->getDiff($this->attribute_map, $diff);
                    $result = $this->endpoint->change($this->attribute_map, $diff, $map, $exists, $simulate);

                    if ($result !== null) {
                        $endpoints['endpoints.'.$this->endpoint->getName().'.id'] = $result;
                    }
                } else {
                    $this->logger->debug('no update required for object on endpoint ['.$this->endpoint->getIdentifier().']', [
                        'category' => get_class($this),
                    ]);
                }

                $endpoints['endpoints.'.$this->endpoint->getName().'.last_sync'] = new UTCDateTime();
                $this->endpoint->getDataType()->changeObject($object, $object->getData(), $simulate, $endpoints);

                return true;

            break;
            default:
                throw new InvalidArgumentException('invalid value for ensure in workflow given, only absent, exists or last is allowed');
        }

        return false;
    }

    /**
     * Create object relations.
     */
    protected function importRelations(DataObjectInterface $object, array $data): bool
    {
        foreach ($this->attribute_map->getMap() as $name => $definition) {
            if (isset($definition['map'])) {
                $mandator = $this->endpoint->getDataType()->getMandator();
                $datatype = $mandator->getDataType($definition['map']['datatype']);
                $relative = $datatype->getObject([
                    'data.'.$definition['map']['to'] => $data[$name],
                ]);

                $object->createRelation($relative);
            }
        }

        return true;
    }

    /**
     * check condition.
     */
    protected function checkCondition(Iterable $object, bool $garbage = false): bool
    {
        if ($this->condition === null) {
            $this->logger->debug('no workflow condiditon set for workflow ['.$this->getIdentifier().']', [
                'category' => get_class($this),
            ]);

            return true;
        }

        $this->logger->debug('execute workflow condiditon ['.$this->condition.'] for workflow ['.$this->getIdentifier().']', [
            'category' => get_class($this),
        ]);

        try {
            return (bool) $this->expression->evaluate($this->condition, [
                'object' => $object,
                'garbage' => $garbage,
            ]);
        } catch (\Exception $e) {
            $this->logger->warning('failed execute workflow condition ['.$this->condition.']', [
                'category' => get_class($this),
            ]);

            return false;
        }
    }

    /**
     * Get import object.
     */
    protected function getImportObject(DataTypeInterface $datatype, array $map, UTCDateTime $ts): ?DataObjectInterface
    {
        $filter = array_intersect_key($map, array_flip($this->endpoint->getImport()));
        $prefixed = [];
        foreach ($filter as $attribute => $value) {
            $prefixed['data.'.$attribute] = $value;
        }

        if (count($filter) !== count($this->endpoint->getImport())) {
            throw new Exception\ImportConditionNotMet('import condition attributes are not available from mapping');
        }

        try {
            $exists = $datatype->getObject($prefixed, false);
        } catch (DataObjectException\MultipleFound $e) {
            throw $e;
        } catch (DataObjectException\NotFound $e) {
            return null;
        }

        $endpoints = $exists->getEndpoints();
        if ($exists !== false && isset($endpoints[$this->endpoint->getName()])
        && $endpoints[$this->endpoint->getName()]['last_sync']->toDateTime() >= $ts->toDateTime()) {
            throw new Exception\ImportConditionNotMet('import filter matched multiple source objects');
        }

        return $exists;
    }

    /**
     * Get export object.
     */
    protected function getExportObject(Iterable $map)
    {
        try {
            if ($this->endpoint->flushRequired()) {
                $exists = false;
            } else {
                $exists = $this->endpoint->getOne($map, $this->attribute_map->getAttributes());
            }
        } catch (EndpointException\ObjectMultipleFound $e) {
            throw $e;
        } catch (EndpointException\ObjectNotFound $e) {
            $exists = false;
        }

        return $exists;
    }

    /**
     * Map.
     */
    protected function map(Iterable $object, Iterable $mongodb_object, UTCDateTime $ts): Iterable
    {
        $object = Helper::associativeArrayToPath($object);
        $mongodb_object = Helper::associativeArrayToPath($mongodb_object);

        foreach ($this->attribute_map->getMap() as $attr => $value) {
            if (!isset($value['ensure'])) {
                continue;
            }

            $exists = isset($mongodb_object[$attr]);
            if ($value['ensure'] === WorkflowInterface::ENSURE_EXISTS && $exists === true) {
                continue;
            }
            if (($value['ensure'] === WorkflowInterface::ENSURE_LAST || $value['ensure'] === WorkflowInterface::ENSURE_EXISTS) && isset($object[$attr])) {
                $mongodb_object[$attr] = $object[$attr];
            } elseif ($value['ensure'] === WorkflowInterface::ENSURE_ABSENT && isset($mongodb_object[$attr]) || !isset($object[$attr]) && isset($mongodb_object[$attr])) {
                unset($mongodb_object[$attr]);
            }
        }

        return $mongodb_object;
    }
}
