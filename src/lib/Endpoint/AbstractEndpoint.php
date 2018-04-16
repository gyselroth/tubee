<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use InvalidArgumentException;
use Psr\Log\LoggerInterface as Logger;
use Tubee\DataType\DataTypeInterface;
use Tubee\Helper;
use Tubee\Workflow;
use Tubee\Workflow\WorkflowInterface;

abstract class AbstractEndpoint implements EndpointInterface
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
     * Workflow.
     *
     * @var iterable
     */
    protected $workflows = [];

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
     *
     * @param string            $name
     * @param string            $type
     * @param DataTypeInterface $datatype
     * @param Logger            $logger
     * @param iterable          $config
     */
    public function __construct(string $name, string $type, DataTypeInterface $datatype, Logger $logger, ?Iterable $config = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->datatype = $datatype;
        $this->logger = $logger;
        $this->setOptions($config);

        if ($this->type === EndpointInterface::TYPE_SOURCE && count($this->import) === 0) {
            throw new Exception\SourceEndpointNoImportCondition('source endpoint must include at least one import condition');
        }

        /*if (is_iterable($this->filter_one) && count($this->filter_one) === 0) {
            throw new Exception\FilterOneRequired('endpoint must declare a single object filter');
        }*/
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
     *
     * @param iterable $config
     *
     * @return EndpointInterface
     */
    public function setOptions(?Iterable $config = null): EndpointInterface
    {
        if ($config === null) {
            return $this;
        }

        foreach ($config as $option => $value) {
            switch ($option) {
                case 'flush':
                    $this->flush = (bool) (int) $value;

                break;
                case 'import':
                    $this->import = (array) $value;

                break;
                case 'filter':
                    if (isset($value['all'])) {
                        $this->filter_all = $value['all'];
                    }
                    if (isset($value['one'])) {
                        $this->filter_one = $value['one'];
                    }

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
    public function getImport(): Iterable
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
    public function exists(Iterable $object): bool
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
        return isset($this->workflows[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function injectWorkflow(WorkflowInterface $workflow, string $name): EndpointInterface
    {
        $this->logger->debug('inject workflow ['.$name.'] of type ['.get_class($workflow).']', [
            'category' => get_class($this),
        ]);

        if ($this->hasWorkflow($name)) {
            throw new Exception\WorkflowNotUnique('workflow '.$name.' is already registered');
        }

        $this->workflows[$name] = $workflow;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getWorkflow(string $name): WorkflowInterface
    {
        if (!isset($this->workflows[$name])) {
            throw new Exception\WorkflowNotFound('workflow '.$name.' is not registered in endpoint '.$this->name);
        }

        return $this->workflows[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getWorkflows(Iterable $workflows = []): array
    {
        if (count($workflows) === 0) {
            return $this->workflows;
        }
        $list = [];
        foreach ($workflows as $name) {
            if (!isset($this->workflows[$name])) {
                throw new Exception\WorkflowNotFound('workflow '.$name.' is not registered in endpoint '.$this->name);
            }
            $list[$name] = $this->workflows[$name];
        }

        return $list;
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
    public function getFilterOne(Iterable $object)
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
     *
     * @param string   $string
     * @param iterable $data
     *
     * @return string
     */
    private function parseAttribute(string $string, Iterable $data): string
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
