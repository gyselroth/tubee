<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use Generator;
use MongoDB\BSON\UTCDateTimeInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\DataObject\DataObjectInterface;
use Tubee\DataObject\Exception as DataObjectException;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Endpoint\Exception as EndpointException;
use Tubee\EndpointObject\EndpointObjectInterface;
use Tubee\Resource\AbstractResource;
use Tubee\Resource\AttributeResolver;
use Tubee\V8\Engine as V8Engine;
use Tubee\Workflow\Exception;
use Tubee\Workflow\WorkflowInterface;
use V8Js;

class Workflow extends AbstractResource implements WorkflowInterface
{
    /**
     * Kind.
     */
    public const KIND = 'Workflow';

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
     * V8 engine.
     *
     * @var V8Engine
     */
    protected $v8;

    /**
     * Initialize.
     */
    public function __construct(string $name, string $ensure, V8Engine $v8, AttributeMapInterface $attribute_map, EndpointInterface $endpoint, LoggerInterface $logger, array $resource = [])
    {
        $this->name = $name;
        $this->ensure = $ensure;
        $this->v8 = $v8;
        $this->attribute_map = $attribute_map;
        $this->endpoint = $endpoint;
        $this->logger = $logger;
        $this->resource = $resource;
        $this->condition = $resource['data']['condition'];
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): string
    {
        return $this->endpoint->getIdentifier().'::'.$this->name;
    }

    /**
     * Get ensure.
     */
    public function getEnsure(): string
    {
        return $this->ensure;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(ServerRequestInterface $request): array
    {
        $namespace = $this->endpoint->getCollection()->getResourceNamespace()->getName();
        $collection = $this->endpoint->getCollection()->getName();
        $endpoint = $this->endpoint->getName();

        $resource = [
            '_links' => [
                'namespace' => ['href' => (string) $request->getUri()->withPath('/api/v1/namespaces/'.$namespace)],
                'collection' => ['href' => (string) $request->getUri()->withPath('/api/v1/namespaces/'.$namespace.'/collections/'.$collection)],
                'endpoint' => ['href' => (string) $request->getUri()->withPath('/api/v1/namespaces/'.$namespace.'/collections/'.$collection.'/endpoints/'.$endpoint)],
           ],
            'namespace' => $namespace,
            'collection' => $collection,
            'endpoint' => $endpoint,
            'data' => $this->getData(),
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
    public function cleanup(DataObjectInterface $object, UTCDateTimeInterface $ts, bool $simulate = false): bool
    {
        $attributes = $object->toArray();
        if ($this->checkCondition($attributes) === false) {
            return false;
        }

        $attributes = Helper::associativeArrayToPath($attributes);

        var_dump($attributes);

        $map = $this->attribute_map->map($attributes, $ts);
        $this->logger->info('mapped object attributes [{map}] for cleanup', [
            'category' => get_class($this),
            'map' => array_keys($map),
        ]);

        switch ($this->ensure) {
            case WorkflowInterface::ENSURE_ABSENT:
                $this->importRelations($exists, $map, $simulate);

                return $this->endpoint->getCollection()->deleteObject($object->getId(), $simulate);

            break;
            default:
            case WorkflowInterface::ENSURE_LAST:
                $resource = $this->map($map, ['data' => $object->getData()], $ts);
                $object->getCollection()->changeObject($object, $resource, $simulate);
                $this->importRelations($object, $map, $simulate);

                return true;

            break;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function import(CollectionInterface $collection, EndpointObjectInterface $object, UTCDateTimeInterface $ts, bool $simulate = false): bool
    {
        $object = $object->getData();
        if ($this->checkCondition($object) === false) {
            return false;
        }

        $map = $this->attribute_map->map($object, $ts);
        $this->logger->info('mapped object attributes [{map}] for import', [
            'category' => get_class($this),
            'map' => array_keys($map),
        ]);

        $exists = $this->getImportObject($collection, $map, $object, $ts);

        if ($exists === null) {
            $this->logger->info('found no existing data object for given import attributes', [
                'category' => get_class($this),
            ]);
        } else {
            $this->logger->info('identified existing data object ['.$exists->getId().'] for import', [
                'category' => get_class($this),
            ]);
        }

        $ensure = $this->ensure;
        if ($exists !== null && $this->ensure === WorkflowInterface::ENSURE_EXISTS) {
            return false;
        }
        if ($exists === null && $this->ensure === WorkflowInterface::ENSURE_LAST) {
            $ensure = WorkflowInterface::ENSURE_EXISTS;
        }

        switch ($ensure) {
            case WorkflowInterface::ENSURE_ABSENT:
                $collection->deleteObject($exists->getId(), $simulate);

                return true;

            break;
            case WorkflowInterface::ENSURE_EXISTS:
                $endpoints = [
                    $this->endpoint->getName() => [
                        'last_sync' => $ts,
                        'garbage' => false,
                    ],
                ];

                $id = $collection->createObject(Helper::pathArrayToAssociative($map), $simulate, $endpoints);
                $this->importRelations($collection->getObject(['_id' => $id]), $map, $simulate, $endpoints);

                return true;

            break;
            default:
            case WorkflowInterface::ENSURE_LAST:
                $object = $this->map($map, ['data' => $exists->getData()], $ts);
                $endpoints = [
                    $this->endpoint->getName() => [
                        'last_sync' => $ts,
                        'garbage' => false,
                    ],
                ];

                $collection->changeObject($exists, $object, $simulate, $endpoints);
                $this->importRelations($exists, $map, $simulate, $endpoints);

                return true;

            break;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function export(DataObjectInterface $object, UTCDateTimeInterface $ts, bool $simulate = false): bool
    {
        $attributes = $object->toArray();
        $attributes['relations'] = iterator_to_array($this->getRelations($object));
        if ($this->checkCondition($attributes) === false) {
            return false;
        }

        $map = $this->attribute_map->map($attributes, $ts);
        $this->logger->info('mapped object attributes [{map}] for write', [
            'category' => get_class($this),
            'map' => array_keys($map),
        ]);

        $exists = $this->getExportObject([
            'map' => $map,
            'object' => $attributes,
        ]);

        $map = Helper::pathArrayToAssociative($map);
        $ensure = $this->ensure;

        if ($exists === null && $this->ensure === WorkflowInterface::ENSURE_ABSENT) {
            $this->logger->info('skip object which is already absent from endpoint ['.$this->endpoint->getIdentifier().']', [
                'category' => get_class($this),
            ]);

            return true;
        }
        if ($exists !== null && $this->ensure === WorkflowInterface::ENSURE_EXISTS) {
            return false;
        }

        if ($exists === null && $this->ensure === WorkflowInterface::ENSURE_LAST) {
            $ensure = WorkflowInterface::ENSURE_EXISTS;
        }

        switch ($ensure) {
            case WorkflowInterface::ENSURE_ABSENT:
                $this->logger->info('delete existing object from endpoint ['.$this->endpoint->getIdentifier().']', [
                    'category' => get_class($this),
                ]);

                $this->endpoint->delete($this->attribute_map, $map, $exists->getData(), $simulate);

                return true;

            break;
            case WorkflowInterface::ENSURE_EXISTS:
                $this->logger->info('create new object on endpoint ['.$this->endpoint->getIdentifier().']', [
                    'category' => get_class($this),
                ]);

                $result = $this->endpoint->create($this->attribute_map, $map, $simulate);

                $endpoints = [
                    $this->endpoint->getName() => [
                        'last_sync' => $ts,
                        'result' => $result,
                        'garbage' => false,
                    ],
                ];

                $this->endpoint->getCollection()->changeObject($object, $object->toArray(), $simulate, $endpoints);

                return true;

            break;
            default:
            case WorkflowInterface::ENSURE_LAST:
                $this->logger->info('change object on endpoint ['.$this->endpoint->getIdentifier().']', [
                    'category' => get_class($this),
                ]);
                $diff = $this->attribute_map->getDiff($map, $exists->getData());

                $endpoints = [$this->endpoint->getName() => [
                    'last_sync' => $ts,
                    'garbage' => false,
                ]];

                if (count($diff) > 0) {
                    $this->logger->info('update object on endpoint ['.$this->endpoint->getIdentifier().'] with attributes [{attributes}]', [
                        'category' => get_class($this),
                        'attributes' => $diff,
                    ]);

                    $diff = $this->endpoint->getDiff($this->attribute_map, $diff);

                    $this->logger->debug('execute diff [{diff}] on endpoint ['.$this->endpoint->getIdentifier().']', [
                        'category' => get_class($this),
                        'diff' => $diff,
                    ]);

                    $result = $this->endpoint->change($this->attribute_map, $diff, $map, $exists->getData(), $simulate);

                    if ($result !== null) {
                        $endpoints[$this->endpoint->getName()]['result'] = $result;
                    }
                } else {
                    $this->logger->debug('object on endpoint ['.$this->endpoint->getIdentifier().'] is already up2date', [
                        'category' => get_class($this),
                    ]);
                }

                if (!isset($endpoints[$this->endpoint->getName()]['result'])) {
                    if (isset($exists->getData()[$this->endpoint->getResourceIdentifier()])) {
                        $endpoints[$this->endpoint->getName()]['result'] = $exists->getData()[$this->endpoint->getResourceIdentifier()];
                    } else {
                        $endpoints[$this->endpoint->getName()]['result'] = null;
                    }
                }

                $this->endpoint->getCollection()->changeObject($object, $object->toArray(), $simulate, $endpoints);

                return true;

            break;
        }

        return false;
    }

    /**
     * Transform relations to array.
     */
    protected function getRelations(DataObjectInterface $object): Generator
    {
        foreach ($object->getRelations() as $relation) {
            $resource = $relation->toArray();
            $resource['object'] = $relation->getDataObject()->toArray();
            yield $resource;
        }
    }

    /**
     * Create object relations.
     */
    protected function importRelations(DataObjectInterface $object, array $data, bool $simulate, array $endpoints = []): bool
    {
        $this->logger->debug('find relationships to be imported for object ['.$object->getId().']', [
            'category' => get_class($this),
        ]);

        foreach ($this->attribute_map->getMap() as $definition) {
            if (!isset($definition['map'])) {
                continue;
            }

            if (!isset($data[$definition['name']])) {
                $this->logger->debug('relation attribute ['.$definition['map']['collection'].':'.$definition['map']['to'].'] not found in mapped data object', [
                    'category' => get_class($this),
                ]);

                continue;
            }

            $this->logger->debug('find related object from ['.$object->getId().'] to ['.$definition['map']['collection'].':'.$definition['map']['to'].'] => ['.$data[$definition['name']].']', [
                'category' => get_class($this),
            ]);

            $namespace = $this->endpoint->getCollection()->getResourceNamespace();
            $collection = $namespace->getCollection($definition['map']['collection']);
            $relative = $collection->getObject([
                $definition['map']['to'] => $data[$definition['name']],
            ]);

            $this->logger->debug('ensure relation state ['.$definiton['map']['ensure'].'] for relation to ['.$relative->getId().']', [
                'category' => get_class($this),
            ]);

            switch ($definiton['map']['ensure']) {
                case WorkflowInterface::ENSURE_ABSENT:
                    $object->removeRelation($relative, $simulate);

                break;
                default:
                case WorkflowInterface::ENSURE_EXISTS:
                case WorkflowInterface::ENSURE_LAST:
                    $object->createOrUpdateRelation($relative, [], $simulate, $endpoints);

                break;
            }
        }

        return true;
    }

    /**
     * check condition.
     */
    protected function checkCondition(array $object): bool
    {
        if ($this->condition === null) {
            $this->logger->debug('no workflow condition set for workflow ['.$this->getIdentifier().']', [
                'category' => get_class($this),
            ]);

            return true;
        }

        $this->logger->debug('execute workflow condiditon ['.$this->condition.'] for workflow ['.$this->getIdentifier().']', [
            'category' => get_class($this),
        ]);

        try {
            $this->v8->object = $object;
            $this->v8->executeString($this->condition, '', V8Js::FLAG_FORCE_ARRAY);

            return (bool) $this->v8->getLastResult();
        } catch (\Exception $e) {
            $this->logger->error('failed execute workflow condition ['.$this->condition.']', [
                'category' => get_class($this),
                'exception' => $e,
            ]);

            return false;
        }
    }

    /**
     * Get import object.
     */
    protected function getImportObject(CollectionInterface $collection, array $map, array $object, UTCDateTimeInterface $ts): ?DataObjectInterface
    {
        $filter = array_intersect_key($map, array_flip($this->endpoint->getImport()));
        //TODO: debug import line here

        if (empty($filter) || count($filter) !== count($this->endpoint->getImport())) {
            throw new Exception\ImportConditionNotMet('import condition attributes are not available from mapping');
        }

        try {
            $exists = $collection->getObject($filter, false);
        } catch (DataObjectException\MultipleFound $e) {
            throw $e;
        } catch (DataObjectException\NotFound $e) {
            return null;
        }

        /*$endpoints = $exists->getEndpoints();

        if ($exists !== false && isset($endpoints[$this->endpoint->getName()])
        && $endpoints[$this->endpoint->getName()]['last_sync']->toDateTime() >= $ts->toDateTime()) {
            throw new Exception\ImportConditionNotMet('import filter matched multiple source objects');
        }*/

        return $exists;
    }

    /**
     * Get export object.
     */
    protected function getExportObject(array $map): ?EndpointObjectInterface
    {
        try {
            if ($this->endpoint->flushRequired()) {
                $exists = null;
            } else {
                $exists = $this->endpoint->getOne($map, $this->attribute_map->getAttributes());
            }

            $this->logger->debug('found existing object on destination endpoint with provided filter_one', [
                'category' => get_class($this),
            ]);

            return $exists;
        } catch (EndpointException\ObjectMultipleFound $e) {
            throw $e;
        } catch (EndpointException\ObjectNotFound $e) {
            $this->logger->debug('object does not exists yet on destination endpoint', [
                'category' => get_class($this),
                'exception' => $e,
            ]);
        } catch (EndpointException\AttributeNotResolvable $e) {
            $this->logger->debug('object filter can not be resolved, leading to non existing object', [
                'category' => get_class($this),
                'exception' => $e,
            ]);
        }

        return null;
    }

    /**
     * Map.
     */
    protected function map(array $object, array $mongodb_object, UTCDateTimeInterface $ts): Iterable
    {
        $object = Helper::associativeArrayToPath($object);
        $mongodb_object = Helper::associativeArrayToPath($mongodb_object);

        foreach ($this->attribute_map->getMap() as $name => $value) {
            $name = isset($value['name']) ? $value['name'] : $name;
            $exists = isset($mongodb_object[$name]);
            if ($value['ensure'] === WorkflowInterface::ENSURE_EXISTS && $exists === true) {
                continue;
            }
            if (($value['ensure'] === WorkflowInterface::ENSURE_LAST || $value['ensure'] === WorkflowInterface::ENSURE_EXISTS) && isset($object[$name])) {
                $mongodb_object[$name] = $object[$name];
            } elseif ($value['ensure'] === WorkflowInterface::ENSURE_ABSENT && isset($mongodb_object[$name]) || !isset($object[$name]) && isset($mongodb_object[$name])) {
                unset($mongodb_object[$name]);
            }
        }

        return Helper::pathArrayToAssociative($mongodb_object);
    }
}
