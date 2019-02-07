<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Resource\AbstractResource;
use Tubee\Resource\AttributeResolver;
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
}
