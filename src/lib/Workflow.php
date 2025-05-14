<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Tubee\Async\Sync;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\DataObject\DataObjectInterface;
use Tubee\DataObjectRelation\Factory as DataObjectRelationFactory;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Resource\AbstractResource;
use Tubee\Resource\AttributeResolver;
use Tubee\Resource\Factory as ResourceFactory;
use Tubee\V8\Engine as V8Engine;
use Tubee\Workflow\Map;
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
     * Data object relation factory.
     *
     * @var DataObjectRelationFactory
     */
    protected $relation_factory;

    /**
     * Resource factory.
     *
     * @var ResourceFactory
     */
    protected $resource_factory;

    /**
     * Initialize.
     */
    public function __construct(string $name, string $ensure, V8Engine $v8, AttributeMapInterface $attribute_map, EndpointInterface $endpoint, LoggerInterface $logger, ResourceFactory $resource_factory, DataObjectRelationFactory $relation_factory, array $resource = [])
    {
        $this->name = $name;
        $this->ensure = $ensure;
        $this->v8 = $v8;
        $this->attribute_map = $attribute_map;
        $this->endpoint = $endpoint;
        $this->logger = $logger;
        $this->resource = $resource;
        $this->condition = $resource['data']['condition'];
        $this->resource_factory = $resource_factory;
        $this->relation_factory = $relation_factory;
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
     * Update object.
     */
    protected function updateObject(DataObjectInterface $object, bool $simulate, Sync $process, ?string $result, array $status): bool
    {
        $status = array_merge([
            'name' => $this->endpoint->getName(),
            'last_sync' => $process->getTimestamp(),
            'process' => $process->getId(),
            'workflow' => $this->getName(),
        ], $status);

        if ($status['success'] === true) {
            $status['last_successful_sync'] = $process->getTimestamp();
        }

        if ($result !== null) {
            $status['result'] = $result;
        }

        if ($status['exception'] !== null) {
            $exception = $status['exception'];
            $status['exception'] = [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile().':'.$exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        $this->endpoint->getCollection()->changeObject($object, $object->toArray(), $simulate, $status);

        return true;
    }
}
