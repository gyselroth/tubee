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
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Tubee\DataType\DataTypeInterface;
use Tubee\Helper;
use Tubee\Resource\AbstractResource;
use Tubee\Resource\AttributeResolver;
use Tubee\Workflow\Factory as WorkflowFactory;
use Tubee\Workflow\WorkflowInterface;

abstract class AbstractEndpoint extends AbstractResource implements EndpointInterface
{
    /**
     * Endpoint name.
     *
     * @var string
     */
    protected $name;

    /**
     * DataTypeInterface.
     *
     * @var DataTypeInterface
     */
    protected $datatype;

    /**
     * Logger.
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Filter All.
     *
     * @var mixed
     */
    protected $filter_all = [];

    /**
     * Filter One.
     *
     * @var mixed
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
     * History.
     *
     * @var bool
     */
    protected $history = false;

    /**
     * Init endpoint.
     */
    public function __construct(string $name, string $type, DataTypeInterface $datatype, WorkflowFactory $workflow, LoggerInterface $logger, array $resource = [])
    {
        $this->name = $name;
        $this->type = $type;
        $this->resource = $resource;
        $this->datatype = $datatype;
        $this->logger = $logger;
        $this->workflow = $workflow;

        if (isset($resource['data_options'])) {
            $this->setOptions($resource['data_options']);
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
                    $this->flush = (bool) $value;

                break;
                case 'import':
                    $this->import = (array) $value;

                break;
                case 'filter_one':
                        $this->filter_one = $value;

                break;
                case 'filter_all':
                        $this->filter_all = $value;

                break;
                case 'history':
                    $this->history = (bool) $value;

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
        $datatype = $this->getDataType();
        $mandator = $datatype->getMandator();

        $resource = [
            '_links' => [
                'self' => ['href' => (string) $request->getUri()],
                'mandator' => ['href' => ($mandator = (string) $request->getUri()->withPath('/api/v1/mandators/'.$mandator->getName()))],
                'datatype' => ['href' => $mandator.'/datatypes'.$datatype->getName()],
           ],
            'kind' => 'Endpoint',
            'name' => $this->name,
            'type' => $this->type,
            'resource' => $this->resource['resource'],
            'data_options' => [
                'import' => $this->import,
                'history' => $this->history,
                'flush' => $this->flush,
                'filter_one' => $this->filter_one,
                'filter_all' => $this->filter_all,
            ],
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
    public function getDataType(): DataTypeInterface
    {
        return $this->datatype;
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
    public function getHistory(): bool
    {
        return $this->history;
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
        return $this->workflow->has($this, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function getWorkflow(string $name): WorkflowInterface
    {
        return $this->workflow->getOne($this, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function getWorkflows(array $workflows = [], ?int $offset = null, ?int $limit = null): Generator
    {
        return $this->workflow->getAll($this, $workflows, $offset, $limit);
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
        return $this->datatype->getIdentifier().'::'.$this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterOne(array $object)
    {
        if (is_iterable($this->filter_one)) {
            $filter = [];
            foreach ($this->filter_one as $key => $attr) {
                $filter[$key] = $this->parseAttribute($attr, $object);
            }

            return $filter;
        }

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
     * Parse and replace string with attribute values.
     */
    private function parseAttribute(string $string, array $data): string
    {
        return preg_replace_callback('/(\{(([^\}]*)+)\})(\}?)/', function ($match) use ($string, $data) {
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
