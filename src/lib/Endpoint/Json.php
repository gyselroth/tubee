<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2021 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use Generator;
use Helmich\MongoMock\MockCollection;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\Json\Exception as JsonException;
use Tubee\EndpointObject\EndpointObjectInterface;
use Tubee\Storage\StorageInterface;
use Tubee\Workflow\Factory as WorkflowFactory;

class Json extends AbstractFile
{
    use LoggerTrait;

    /**
     * Kind.
     */
    public const KIND = 'JsonEndpoint';

    /**
     * Init endpoint.
     */
    public function __construct(string $name, string $type, string $file, StorageInterface $storage, CollectionInterface $collection, WorkflowFactory $workflow, LoggerInterface $logger, array $resource = [])
    {
        if ($type === EndpointInterface::TYPE_DESTINATION) {
            $this->flush = true;
        }

        parent::__construct($name, $type, $file, $storage, $collection, $workflow, $logger, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function setup(bool $simulate = false): EndpointInterface
    {
        $streams = $this->storage->openReadStreams($this->file);

        if ($this->type === EndpointInterface::TYPE_DESTINATION) {
            $this->writable = $this->storage->openWriteStream($this->file);
        }

        foreach ($streams as $path => $stream) {
            $content = [];
            if ($this->type === EndpointInterface::TYPE_SOURCE) {
                $content = json_decode(stream_get_contents($stream), true);

                if ($err = json_last_error() !== JSON_ERROR_NONE) {
                    throw new JsonException\InvalidJson('failed decode json '.$this->file.', json error '.$err);
                }

                if (!is_array($content)) {
                    throw new JsonException\ArrayExpected('json file contents must be an array');
                }
            }

            $this->files[] = [
                'stream' => $stream,
                'content' => $content,
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
        foreach ($this->files as $resource) {
            if ($simulate === false && $this->type === EndpointInterface::TYPE_DESTINATION) {
                $json = json_encode($resource['content'], JSON_PRETTY_PRINT);
                if (fwrite($this->writable, $json) === false) {
                    throw new Exception\WriteOperationFailed('failed create json file '.$resource['path']);
                }
                $this->storage->syncWriteStream($this->writable, $resource['path']);
                fclose($resource['stream']);
            } else {
                fclose($resource['stream']);
            }
        }

        $this->files = [];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function transformQuery(?array $query = null)
    {
        $result = null;
        if ($this->filter_all !== null) {
            $result = json_decode(stripslashes($this->filter_all), true);
        }

        if (!empty($query)) {
            if ($this->filter_all === null) {
                $result = json_decode($query, true);
            } else {
                $result = array_merge($result, $query);
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
        $this->logGetAll($filter);
        $i = 0;

        foreach ($this->files as $resource_key => $json) {
            foreach ($json['content'] as $object) {
                $collection = new MockCollection();
                $collection->documents[] = $object;

                if ($collection->count($filter)) {
                    yield $this->build($object);
                    ++$i;
                }
            }
        }

        return $i;
    }

    /**
     * {@inheritdoc}
     */
    public function create(AttributeMapInterface $map, array $object, bool $simulate = false): ?string
    {
        $this->logCreate($object);

        foreach ($this->files as $resource_key => $xml) {
            $this->files[$resource_key]['content'][] = $object;

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
    public function change(AttributeMapInterface $map, array $diff, array $object, EndpointObjectInterface $endpoint_object, bool $simulate = false): ?string
    {
        throw new Exception\UnsupportedEndpointOperation('endpoint '.get_class($this).' does not support change(), use flush=true');
    }

    /**
     * {@inheritdoc}
     */
    public function delete(AttributeMapInterface $map, array $object, EndpointObjectInterface $endpoint_object, bool $simulate = false): bool
    {
        throw new Exception\UnsupportedEndpointOperation('endpoint '.get_class($this).' does not support delete(), use flush=true');
    }

    /**
     * {@inheritdoc}
     */
    public function getOne(array $object, array $attributes = []): EndpointObjectInterface
    {
        $elements = [];
        $filter = $this->getFilterOne($object);
        $this->logGetOne($filter);

        foreach ($this->getAll($filter) as $object) {
            return $this->build($object, $filter);
        }

        throw new Exception\ObjectNotFound('no object found with filter '.json_encode($filter));
    }
}
