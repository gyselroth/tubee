<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use Generator;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\EndpointObject;
use Tubee\EndpointObject\EndpointObjectInterface;
use Tubee\Helper;
use Tubee\Resource\AbstractResource;
use Tubee\Resource\AttributeResolver;
use Tubee\Workflow\Factory as WorkflowFactory;
use Tubee\Workflow\WorkflowInterface;

abstract class AbstractEndpoint extends AbstractResource implements EndpointInterface
{
    /**
     * Kind.
     */
    public const KIND = 'Endpoint';

    /**
     * Endpoint name.
     *
     * @var string
     */
    protected $name;

    /**
     * CollectionInterface.
     *
     * @var CollectionInterface
     */
    protected $collection;

    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Filter All.
     *
     * @var string
     */
    protected $filter_all;

    /**
     * Filter One.
     *
     * @var string
     */
    protected $filter_one;

    /**
     * Import.
     *
     * @var array
     */
    protected $import = [];

    /**
     * Type.
     *
     * @var string
     */
    protected $type;

    /**
     * Flush.
     *
     * @var bool
     */
    protected $flush = false;

    /**
     * Workflow factory.
     *
     * @var WorkflowFactory
     */
    protected $workflow_factory;

    /**
     * Endpoint object identifiers.
     *
     * @var string
     */
    protected $identifier;

    /**
     * Init endpoint.
     */
    public function __construct(string $name, string $type, CollectionInterface $collection, WorkflowFactory $workflow_factory, LoggerInterface $logger, array $resource = [])
    {
        $this->name = $name;
        $this->type = $type;
        $this->resource = $resource;
        $this->collection = $collection;
        $this->logger = $logger;
        $this->workflow_factory = $workflow_factory;

        if (isset($resource['data']['options'])) {
            $this->setOptions($resource['data']['options']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setup(bool $simulate = false): EndpointInterface
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getKind(): string
    {
        return $this->resource['kind'];
    }

    /**
     * Set options.
     */
    public function setOptions(?array $config = null): EndpointInterface
    {
        if ($config === null) {
            return $this;
        }

        foreach ($config as $option => $value) {
            switch ($option) {
                case 'flush':
                case 'import':
                case 'identifier':
                case 'filter_one':
                case 'filter_all':
                    $this->{$option} = $value;

                break;
                default:
                    throw new InvalidArgumentException('unknown option '.$option.' given');
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(ServerRequestInterface $request): array
    {
        $collection = $this->collection->getName();
        $namespace = $this->collection->getResourceNamespace()->getName();

        $resource = [
            '_links' => [
                'namespace' => ['href' => (string) $request->getUri()->withPath('/api/v1/namespaces/'.$namespace)],
                'collection' => ['href' => (string) $request->getUri()->withPath('/api/v1/namespaces/'.$namespace.'/collections/'.$collection)],
           ],
            'kind' => static::KIND,
            'namespace' => $namespace,
            'collection' => $collection,
            'data' => $this->getData(),
            'status' => function ($endpoint) {
                try {
                    $endpoint->setup();

                    return [
                        'available' => true,
                    ];
                } catch (\Exception $e) {
                    return [
                        'available' => false,
                        'exception' => get_class($e),
                        'error' => $e->getMessage(),
                        'code' => $e->getCode(),
                    ];
                }
            },
        ];

        return AttributeResolver::resolve($request, $this, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown(bool $simulate = false): EndpointInterface
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function flushRequired(): bool
    {
        return $this->flush;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(bool $simulate = false): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(): CollectionInterface
    {
        return $this->collection;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceIdentifier(): ?string
    {
        return $this->identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function getImport(): array
    {
        return $this->import;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(array $object): bool
    {
        try {
            $this->getOne($object, []);

            return true;
        } catch (Exception\ObjectMultipleFound $e) {
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasWorkflow(string $name): bool
    {
        return $this->workflow_factory->has($this, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function getWorkflow(string $name): WorkflowInterface
    {
        return $this->workflow_factory->getOne($this, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function getWorkflows(array $workflows = [], ?int $offset = null, ?int $limit = null): Generator
    {
        return $this->workflow_factory->getAll($this, $workflows, $offset, $limit, [
            'priority' => 1,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): string
    {
        return $this->collection->getIdentifier().'::'.$this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterOne(array $object)
    {
        return $this->parseAttribute($this->filter_one, $object);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterAll()
    {
        return $this->filter_all;
    }

    /**
     * Build endpoint object.
     */
    protected function build(array $object): EndpointObjectInterface
    {
        return new EndpointObject(['data' => $object], $this);
    }

    /**
     * Parse and replace string with attribute values.
     */
    private function parseAttribute(string $string, array $data): string
    {
        return preg_replace_callback('/(\{(([^\}\{\"]*)+)\})/', function ($match) use ($string, $data) {
            if (substr($match[0], 0, 2) === '{{' && $match[4][0] === '}') {
                return $match[2].$match[4];
            }

            $attribute = $match[2];

            try {
                return Helper::getArrayValue($data, $attribute);
            } catch (\Exception $e) {
                throw new Exception\AttributeNotResolvable('could not resolve attribute '.$attribute.' in value '.$string);
            }
        }, $string);
    }
}
