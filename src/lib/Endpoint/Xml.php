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
use DOMNode;
use Generator;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\Xml\Exception as XmlException;
use Tubee\Endpoint\Xml\QueryTransformer;
use Tubee\EndpointObject\EndpointObjectInterface;
use Tubee\Storage\StorageInterface;
use Tubee\Workflow\Factory as WorkflowFactory;

class Xml extends AbstractFile
{
    /**
     * Kind.
     */
    public const KIND = 'XmlEndpoint';

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
     * Init endpoint.
     */
    public function __construct(string $name, string $type, string $file, StorageInterface $storage, CollectionInterface $collection, WorkflowFactory $workflow, LoggerInterface $logger, array $resource = [])
    {
        if (isset($resource['data']['resource'])) {
            $this->setXmlOptions($resource['data']['resource']);
        }

        parent::__construct($name, $type, $file, $storage, $collection, $workflow, $logger, $resource);
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
            } else {
                $this->logger->debug('decode xml stream from ['.$path.']', [
                    'category' => get_class($this),
                ]);

                if ($dom->loadXML($content) === false) {
                    throw new XmlException\InvalidXml('could not decode xml stream from '.$path.'');
                }

                $xml_root = $dom->documentElement;

                if (!$xml_root->hasChildNodes()) {
                    $level = $this->type === EndpointInterface::TYPE_SOURCE ? 'warning' : 'debug';

                    $this->logger->$level('empty xml file ['.$path.'] given', [
                        'category' => get_class($this),
                    ]);
                }
            }

            $this->files[] = [
                'dom' => $dom,
                'xml_root' => $xml_root,
                'path' => $path,
                'stream' => $stream,
            ];
        }

        return $this;
    }

    /**
     * Set options.
     */
    public function setXmlOptions(?array $config = null): EndpointInterface
    {
        if ($config === null) {
            return $this;
        }

        foreach ($config as $option => $value) {
            switch ($option) {
                case 'node_name':
                case 'root_name':
                case 'pretty':
                case 'preserve_whitespace':
                    $this->{$option} = $value;

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
        foreach ($this->files as $resource) {
            if ($simulate === false && $this->type === EndpointInterface::TYPE_DESTINATION) {
                $this->flush($simulate);
                if (fwrite($resource['stream'], $resource['dom']->saveXML()) === false) {
                    throw new Exception\WriteOperationFailed('failed create xml file '.$resource['path']);
                }

                $this->storage->syncWriteStream($resource['stream'], $resource['path']);
            }

            fclose($resource['stream']);
        }

        $this->files = [];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function transformQuery(?array $query = null)
    {
        $pre = '//'.$this->node_name;
        $result = $pre;

        if ($this->filter_all !== null) {
            $result = $pre.'['.$this->filter_all.']';
        }

        if (!empty($query)) {
            if ($this->filter_all === null) {
                $result = $pre.'['.QueryTransformer::transform($query).']';
            } else {
                $result = $pre.'['.$this->filter_all.' and '.QueryTransformer::transform($query).']';
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(?array $query = null): Generator
    {
        $filter = $this->transformQuery($query);
        $i = 0;

        foreach ($this->files as $xml) {
            $this->logger->debug('find xml nodes with xpath ['.$filter.'] in ['.$xml['path'].'] on endpoint ['.$this->getIdentifier().']', [
                'category' => get_class($this),
            ]);

            $xpath = new \DOMXPath($xml['dom']);
            $node = $xpath->query($filter);

            foreach ($node as $result) {
                $result = $this->xmlToArray($result);
                yield $this->build($result);
                ++$i;
            }
        }

        return $i;
    }

    /**
     * {@inheritdoc}
     */
    public function create(AttributeMapInterface $map, array $object, bool $simulate = false): ?string
    {
        $xml = $this->files[0];
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
    public function change(AttributeMapInterface $map, array $diff, array $object, array $endpoint_object, bool $simulate = false): ?string
    {
        $xml = $this->files[0];
        $attrs = [];
        $filter = $this->getFilterOne($object);
        $xpath = new \DOMXPath($xml['dom']);
        $node = $xpath->query($filter);
        $node = $node[0];

        foreach ($diff as $attribute => $update) {
            $child = $this->getChildNode($node, $attribute);

            switch ($update['action']) {
                case AttributeMapInterface::ACTION_REPLACE:
                    if (is_array($update['value'])) {
                        $new = $xml['dom']->createElement($attribute);
                        foreach ($update['value'] as $val) {
                            $new->appendChild($xml['dom']->createElement($attribute, $val));
                        }
                    } else {
                        $new = $xml['dom']->createElement($attribute, $update['value']);
                    }

                    $node->replaceChild($new, $child);

                break;
                case AttributeMapInterface::ACTION_REMOVE:
                    $node->removeChild($child);

                break;
                case AttributeMapInterface::ACTION_ADD:
                    $child->appendChild($xml['dom']->createElement($attribute, $update['value']));

                break;
                default:
                    throw new InvalidArgumentException('unknown action '.$update['action'].' given');
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(AttributeMapInterface $map, array $object, array $endpoint_object, bool $simulate = false): bool
    {
        $xml = $this->files[0];
        $filter = $this->getFilterOne($object);
        $xpath = new \DOMXPath($xml['dom']);
        $node = $xpath->query($filter);
        $node = $node[0];
        $xml['xml_root']->removeChild($node);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getOne(array $object, array $attributes = []): EndpointObjectInterface
    {
        $filter = $pre = '//'.$this->node_name.'['.$this->getFilterOne($object).']';

        foreach ($this->files as $xml) {
            $this->logger->debug('find xml node with xpath ['.$filter.'] in ['.$xml['path'].'] on endpoint ['.$this->getIdentifier().']', [
                'category' => get_class($this),
            ]);

            $xpath = new \DOMXPath($xml['dom']);
            $nodes = $xpath->query($filter);

            $nodes = iterator_to_array($nodes);

            if (count($nodes) > 1) {
                throw new Exception\ObjectMultipleFound('found more than one object with filter '.$filter);
            }
            if (count($nodes) === 0) {
                throw new Exception\ObjectNotFound('no object found with filter '.$filter);
            }

            $node = $this->xmlToArray(array_shift($nodes));

            return $this->build($node);
        }
    }

    /**
     * Convert XMLElement into nicly formatted php array.
     */
    protected function xmlToArray($root)
    {
        $result = [];

        if ($root->hasAttributes()) {
            $attrs = $root->attributes;
            foreach ($attrs as $attr) {
                $result['@attributes'][$attr->name] = $attr->value;
            }
        }

        if ($root->hasChildNodes()) {
            $children = $root->childNodes;

            if ($children->length == 1) {
                $child = $children->item(0);

                if ($child->nodeType === XML_TEXT_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
                    $result['_value'] = $child->nodeValue;

                    return count($result) == 1 ? $result['_value'] : $result;
                }
            }

            $groups = [];
            foreach ($children as $child) {
                if (!isset($result[$child->nodeName])) {
                    $result[$child->nodeName] = $this->xmlToArray($child);
                } else {
                    if (!isset($groups[$child->nodeName])) {
                        $result[$child->nodeName] = [$result[$child->nodeName]];
                        $groups[$child->nodeName] = 1;
                    }
                    $result[$child->nodeName][] = $this->xmlToArray($child);
                }
            }
        }

        return $result;
    }

    /**
     * Get child node by name.
     */
    protected function getChildNode(DOMNode $node, string $name)
    {
        foreach ($node->childNodes as $child) {
            if ($child->nodeName === $name) {
                return $child;
            }
        }
    }
}
