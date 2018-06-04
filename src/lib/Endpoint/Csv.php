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
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\DataType\DataTypeInterface;
use Tubee\Storage\StorageInterface;

class Csv extends AbstractFile
{
    /**
     * Delimiter.
     *
     * @var string
     */
    protected $delimiter = ',';

    /**
     * Enclosure.
     *
     * @var string
     */
    protected $enclosure = '"';

    /**
     * Escape char.
     *
     * @var string
     */
    protected $escape = '\\';

    /**
     * Init endpoint.
     */
    public function __construct(string $name, string $type, string $file, StorageInterface $storage, DataTypeInterface $datatype, LoggerInterface $logger, ?Iterable $config = null, ?Iterable $csv_options = null)
    {
        if ($type === EndpointInterface::TYPE_DESTINATION) {
            $this->flush = true;
        }

        $this->setCsvOptions($csv_options);
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
            if ($data = fgetcsv($stream, 0, $this->delimiter, $this->enclosure, $this->escape)) {
                $this->logger->debug('use first line in csv as header [{line}]', [
                    'category' => get_class($this),
                    'line' => $data,
                ]);
            } else {
                $level = ($this->type === EndpointInterface::TYPE_SOURCE ? 'warning' : 'debug');
                $this->logger->$level('empty csv file ['.$path.']', [
                    'category' => get_class($this),
                ]);
            }

            $this->resource[] = [
                'resource' => $stream,
                'header' => $data,
            ];
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setCsvOptions(?Iterable $config = null): EndpointInterface
    {
        if ($config === null) {
            return $this;
        }

        foreach ($config as $option => $value) {
            switch ($option) {
                case 'delimiter':
                case 'enclosure':
                case 'escape':
                    $this->{$option} = (string) $value;

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
        foreach ($this->resource as $csv) {
            if ($simulate === false && $this->type === EndpointInterface::TYPE_DESTINATION) {
                $this->storage->syncWriteStream($csv['resource'], $this->file);
            }
            fclose($csv['resource']);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOne(Iterable $object, Iterable $attributes = []): Iterable
    {
        $filter = $this->getFilterOne($object);
        foreach ($this->getAll($filter) as $object) {
            return $object;
        }

        throw new Exception\ObjectNotFound('no object found with filter '.json_encode($filter));
    }

    /**
     * {@inheritdoc}
     */
    public function exists(Iterable $object): bool
    {
        throw new Exception\UnsupportedEndpointOperation('endpoint '.get_class($this).' does not support exists(), use Endpoint\CsvInMemory instead or flush=true');
    }

    /**
     * {@inheritdoc}
     */
    public function getAll($filter = []): Generator
    {
        $filter = array_merge((array) $this->filter_all, (array) $filter);
        foreach ($this->resource as $csv) {
            while (($line = fgetcsv($csv['resource'], 0, $this->delimiter, $this->enclosure, $this->escape)) !== false) {
                $data = array_combine($csv['header'], $line);
                $this->logger->debug('parse csv line [{line}]', [
                    'category' => get_class($this),
                    'line' => $data,
                ]);

                foreach ($filter as $attribute => $value) {
                    if (!array_key_exists($attribute, $data) || is_array($value) && !in_array($data[$attribute], $value) || !is_array($value) && $value !== $data[$attribute]) {
                        $this->logger->debug('line does not match filter [{filter}], skip it', [
                            'category' => get_class($this),
                            'filter' => $filter,
                        ]);

                        continue 2;
                    }
                }

                yield $data;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function create(AttributeMapInterface $map, Iterable $object, bool $simulate = false): ?string
    {
        foreach ($object as $key => $value) {
            if (is_array($value)) {
                throw new Exception\EndpointCanNotHandleArray('endpoint can not handle arrays ["'.$key.'"], did you forget to set a decorator?');
            }
        }

        if ($this->resource[0]['header'] === false) {
            $this->resource[0]['header'] = $map->getAttributes();
            $this->create($map, $this->resource[0]['header'], $simulate);
        }

        if (fputcsv($this->resource[0]['resource'], $object, $this->delimiter, $this->enclosure, $this->escape) === false) {
            throw new Exception\WriteOperationFailed('failed append object to csv file '.$this->file);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getDiff(AttributeMapInterface $map, array $diff): array
    {
        throw new Exception\UnsupportedEndpointOperation('endpoint '.get_class($this).' does not support getDiff(), use Endpoint\CsvInMemory instead or flush=true');
    }

    /**
     * {@inheritdoc}
     */
    public function change(AttributeMapInterface $map, Iterable $diff, Iterable $object, Iterable $endpoint_object, bool $simulate = false): ?string
    {
        throw new Exception\UnsupportedEndpointOperation('endpoint '.get_class($this).' does not support change(), use Endpoint\CsvInMemory instead or flush=true');
    }

    /**
     * {@inheritdoc}
     */
    public function delete(AttributeMapInterface $map, Iterable $object, Iterable $endpoint_object, bool $simulate = false): bool
    {
        throw new Exception\UnsupportedEndpointOperation('endpoint '.get_class($this).' does not support delete(), use Endpoint\CsvInMemory instead or flush=true');
    }
}
