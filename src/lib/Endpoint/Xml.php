<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use DOMDocument;
use Generator;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use SimpleXMLIterator;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\DataType\DataTypeInterface;
use Tubee\Endpoint\Xml\Exception as XmlException;
use Tubee\Storage\StorageInterface;

class Xml extends AbstractFile
{
    /**
     * XML element.
     *
     * @var SimpleXMLElement
     */
    protected $file;

    /**
     * new XML element.
     *
     * @var SimpleXMLElement
     */
    protected $new_xml;

    /**
     * XML root name.
     *
     * @var string
     */
    protected $root_name = 'data';

    /**
     * XMl node name.
     *
     * @var string
     */
    protected $node_name = 'row';

    /**
     * Pretty output.
     *
     * @var bool
     */
    protected $pretty = true;

    /**
     * Preserved whitespace.
     *
     * @var bool
     */
    protected $preserve_whitespace = false;

    /**
     * Resource.
     *
     * @var array
     */
    protected $resource = [];

    /**
     * Init endpoint.
     */
    public function __construct(string $name, string $type, string $file, StorageInterface $storage, DataTypeInterface $datatype, LoggerInterface $logger, ?Iterable $config = null, ?Iterable $xml_options = null)
    {
        $this->setXmlOptions($xml_options);
        parent::__construct($name, $type, $file, $storage, $datatype, $logger, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function setup(bool $simulate = false): EndpointInterface
    {
        if ($this->type === EndpointInterface::TYPE_DESTINATION) {
            $streams = [$this->file => $this->storage->openWriteStream($this->file)];
        } else {
            $streams = $this->storage->openReadStreams($this->file);
        }

        foreach ($streams as $path => $stream) {
            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->formatOutput = $this->pretty;
            $dom->preserveWhiteSpace = $this->preserve_whitespace;

            //read stream into memory since xml operates in-memory
            $content = stream_get_contents($stream);

            if ($this->type === EndpointInterface::TYPE_DESTINATION && empty($content)) {
                $xml_root = $dom->createElement($this->root_name);
                $xml_root = $dom->appendChild($xml_root);
                $xml_element = null;
            } else {
                $this->logger->debug('decode xml stream from ['.$path.']', [
                    'category' => get_class($this),
                ]);

                if ($dom->load($content) === false) {
                    throw new XmlException\InvalidXml('could not decode xml stream from '.$path.'');
                }

                $xml_element = new SimpleXMLIterator($content);
                $xml_root = $dom->documentElement;

                if (!$xml_root->hasChildNodes()) {
                    $level = $this->type === EndpointInterface::TYPE_SOURCE ? 'warning' : 'debug';

                    $this->logger->$level('empty xml file ['.$path.'] given', [
                        'category' => get_class($this),
                    ]);
                }
            }

            $this->resource[] = [
                'dom' => $dom,
                'xml_root' => $xml_root,
                'xml_element' => $xml_element,
                'path' => $path,
            ];
        }

        return $this;
    }

    /**
     * Set options.
     *
     * @param iterable $config
     *
     * @return EndpointInterface
     */
    public function setXmlOptions(?Iterable $config = null): EndpointInterface
    {
        if ($config === null) {
            return $this;
        }

        foreach ($config as $option => $value) {
            switch ($option) {
                case 'node_name':
                case 'root_name':
                    $this->{$option} = (string) $value;

                    break;
                case 'pretty':
                case 'preserve_whitespace':
                    $this->{$option} = (bool) $value;

                    break;
                default:
                    throw new InvalidArgumentException('unknown xml option '.$option.' given');
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown(bool $simulate = false): EndpointInterface
    {
        foreach ($this->resource as $resource) {
            if ($simulate === false && $this->type === EndpointInterface::TYPE_DESTINATION) {
                if (fwrite($resource['stream'], $resource['dom']->saveXML()) === false) {
                    throw new Exception\WriteOperationFailed('failed create xml file '.$resource['path']);
                }

                $this->storage->syncWriteStream($resource['stream'], $resource['path']);
            }

            fclose($resource['stream']);
        }

        $this->resource = [];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAll($filter = []): Generator
    {
        $filtered = [];
        foreach ($this->filter_all as $attr => $value) {
            if (is_iterable($value)) {
                $filtered[$attr] = array_values($value->children());
            } else {
                $filtered[$attr] = $value;
            }
        }

        $filter = array_merge($filtered, (array) $filter);

        foreach ($this->resource as $xml) {
            $data = json_decode(json_encode((array) $xml['xml_element']), true)[$this->node_name];

            if (!isset($data[0])) {
                $node = $data;
                unset($data);
                $data[] = $node;
                unset($node);
            }

            foreach ($data as $node_data) {
                foreach ($filter as $attribute => $value) {
                    if (!array_key_exists($attribute, $node_data) || is_array($value) && !in_array($node_data[$attribute], $value) || !is_array($value) && $value !== $node_data[$attribute]) {
                        $this->logger->debug('data does not match filter [{filter}], skip it', [
                            'category' => get_class($this),
                            'filter' => $filter,
                        ]);

                        continue 2;
                    }
                }

                yield $node_data;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function create(AttributeMapInterface $map, Iterable $object, bool $simulate = false): ?string
    {
        $xml = $this->resource[0];
        $current_track = $xml['dom']->createElement($this->node_name);
        $current_track = $xml['xml_root']->appendChild($current_track);

        foreach ($object as $column => $value) {
            if (is_array($value)) {
                $attr_subnode = $current_track->appendChild($xml['dom']->createElement($column));
                foreach ($value as $val) {
                    $attr_subnode->appendChild($xml['dom']->createElement($column, $val));
                }
            } else {
                $current_track->appendChild($xml['dom']->createElement($column, $value));
            }
        }

        $this->logger->debug('create new xml object on endpoint ['.$this->name.'] with values [{values}]', [
            'category' => get_class($this),
            'values' => $object,
        ]);

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getDiff(AttributeMapInterface $map, array $diff): array
    {
        return $diff;
    }

    /**
     * {@inheritdoc}
     */
    public function change(AttributeMapInterface $map, Iterable $diff, Iterable $object, Iterable $endpoint_object, bool $simulate = false): ?string
    {
        $xml = $this->resource[0];
        $attrs = [];
        $filter = '//'.$this->node_name.$this->getFilterOne($object);

        if ($simulate === false) {
            foreach ($xml['xml_element']->xpath($filter) as $element) {
                if (!isset($element->$key)) {
                    $element->addChild('member');
                }

                $element->$key->addChild($key, $val);
            }

            $xml['dom']->loadXML($xml['xml_element']->asXML());
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(AttributeMapInterface $map, Iterable $object, Iterable $endpoint_object, bool $simulate = false): bool
    {
        $xml = $this->resource[0];
        $filter = '//'.$this->node_name.$this->getFilterOne($object);

        foreach ($xml['xml_element']->xpath($filter) as $element) {
            if (!empty($element)) {
                unset($element[0][0]);
            }
        }
        $xml['dom']->loadXML($xml['xml_element']->asXML());

        $this->logger->info('delete xml object on endpoint ['.$this->name.'] with filter ['.$filter.']', [
            'category' => get_class($this),
        ]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getOne(Iterable $object, Iterable $attributes = []): Iterable
    {
        foreach ($this->resource as $xml) {
            $filter = '//'.$this->node_name.$this->getFilterOne($object);

            if (isset($xml['xml_element'])) {
                $elements = $xml['xml_element']->xpath($filter);
            } else {
                $elements = [];
            }

            if (count($elements) > 1) {
                throw new Exception\ObjectMultipleFound('found more than one object with filter '.$filter);
            }
            if (count($elements) === 0) {
                throw new Exception\ObjectNotFound('no object found with filter '.$filter);
            }

            return json_decode(json_encode((array) array_shift($elements)), true);
        }
    }
}
