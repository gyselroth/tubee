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
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\Endpoint\Json\Exception as JsonException;

class Json extends AbstractFile
{
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
            $content = json_decode(stream_get_contents($stream), true);

            if ($err = json_last_error() !== JSON_ERROR_NONE) {
                throw new JsonException\InvalidJson('failed decode json '.$this->file.', json error '.$err);
            }

            if (!is_array($content)) {
                throw new JsonException\ArrayExpected('json file contents must be an array');
            }

            $this->resource[] = [
                'stream' => $stream,
                'data' => $content,
                'path' => $path,
            ];
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
                $json = json_encode($resource['data'], JSON_PRETTY_PRINT);

                if (fwrite($resource['stream'], $json) === false) {
                    throw new Exception\WriteOperationFailed('failed create json file '.$resource['path']);
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

        foreach ($this->resource as $resource_key => $json) {
            foreach ($json['content'] as $object) {
                if (!is_array($object)) {
                    throw new JsonException\ArrayExpected('json must contain an array of objects');
                }

                if (count(array_intersect_assoc($object, $filter)) !== count($filter)) {
                    $this->logger->debug('json object does not match filter [{filter}], skip it', [
                        'category' => get_class($this),
                        'filter' => $filter,
                    ]);

                    continue;
                }

                yield $object;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function create(AttributeMapInterface $map, Iterable $object, bool $simulate = false): ?string
    {
        foreach ($this->resource as $resource_key => $xml) {
            $this->resource[$resource_key]['content'][] = $object;

            $this->logger->debug('create new json object on endpoint ['.$this->name.'] with values [{values}]', [
                'category' => get_class($this),
                'values' => $object,
            ]);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getDiff(AttributeMapInterface $map, array $diff): array
    {
        throw new Exception\UnsupportedEndpointOperation('endpoint '.get_class($this).' does not support getDiff(), use flush=true');
    }

    /**
     * {@inheritdoc}
     */
    public function change(AttributeMapInterface $map, Iterable $diff, Iterable $object, Iterable $endpoint_object, bool $simulate = false): ?string
    {
        throw new Exception\UnsupportedEndpointOperation('endpoint '.get_class($this).' does not support change(), use flush=true');
    }

    /**
     * {@inheritdoc}
     */
    public function delete(AttributeMapInterface $map, Iterable $object, Iterable $endpoint_object, bool $simulate = false): bool
    {
        throw new Exception\UnsupportedEndpointOperation('endpoint '.get_class($this).' does not support delete(), use flush=true');
    }

    /**
     * {@inheritdoc}
     */
    public function getOne(Iterable $object, Iterable $attributes = []): Iterable
    {
        $elements = [];
        $filter = $this->getFilterOne($object);

        foreach ($this->resource as $json) {
            if (isset($json['content'])) {
                foreach ($json['content'] as $object) {
                    if (!is_array($object)) {
                        throw new JsonException\ArrayExpected('json must contain an array of objects');
                    }

                    if (count(array_intersect_assoc($object, $filter)) !== count($filter)) {
                        continue;
                    }
                    $elements[] = $object;
                }
            }

            if (count($elements) > 1) {
                throw new Exception\ObjectMultipleFound('found more than one object with filter '.json_encode($filter));
            }
            if (count($elements) === 0) {
                throw new Exception\ObjectNotFound('no object found with filter '.json_encode($filter));
            }

            return array_shift($elements);
        }
    }
}
