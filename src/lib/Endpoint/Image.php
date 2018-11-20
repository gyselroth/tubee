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
use Imagick;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\DataType\DataTypeInterface;
use Tubee\EndpointObject\EndpointObjectInterface;
use Tubee\Storage\StorageInterface;
use Tubee\Workflow\Factory as WorkflowFactory;

class Image extends AbstractFile
{
    /**
     * Format.
     *
     * @var string
     */
    protected $format;

    /**
     * Size.
     *
     * @var int
     */
    protected $max_width = 0;

    /**
     * Size.
     *
     * @var int
     */
    protected $max_height = 0;

    /**
     * Init endpoint.
     */
    public function __construct(string $name, string $type, string $file, StorageInterface $storage, DataTypeInterface $datatype, WorkflowFactory $workflow, LoggerInterface $logger, array $resource = [])
    {
        if (isset($resource['image_options'])) {
            $this->setImageOptions($resource['image_options']);
        }

        parent::__construct($name, $type, $file, $storage, $datatype, $workflow, $logger, $resource);
    }

    /**
     * Set image options.
     */
    public function setImageOptions(?array $config = null): EndpointInterface
    {
        if ($config === null) {
            return $this;
        }

        foreach ($config as $option => $value) {
            switch ($option) {
                case 'format':
                    $this->format = (string) $value;

                break;
                case 'max_width':
                case 'max_height':
                    $this->{$option} = (int) $value;

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
    public function transformQuery(?array $query = null)
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getOne(array $object, array $attributes = []): EndpointObjectInterface
    {
        return $this->build([]);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(array $object): bool
    {
        throw new Exception\UnsupportedEndpointOperation('endpoint '.get_class($this).' does not support exists()');
    }

    /**
     * {@inheritdoc}
     */
    public function getAll($filter = []): Generator
    {
        $i = 0;
        foreach ($this->storage->openReadStreams($this->file) as $name => $stream) {
            yield $this->build([
                'name' => $name,
                'content' => $this->scaleImage($stream),
            ]);

            fclose($stream);
            ++$i;
        }

        return $i;
    }

    /**
     * {@inheritdoc}
     */
    public function getDiff(AttributeMapInterface $map, array $diff): array
    {
        throw new Exception\UnsupportedEndpointOperation('endpoint '.get_class($this).' does not support getDiff()');
    }

    /**
     * {@inheritdoc}
     */
    public function create(AttributeMapInterface $map, array $object, bool $simulate = false): ?string
    {
        throw new Exception\UnsupportedEndpointOperation('endpoint '.get_class($this).' does not support create()');
    }

    /**
     * {@inheritdoc}
     */
    public function change(AttributeMapInterface $map, array $diff, array $object, array $endpoint_object, bool $simulate = false): ?string
    {
        throw new Exception\UnsupportedEndpointOperation('endpoint '.get_class($this).' does not support change()');
    }

    /**
     * {@inheritdoc}
     */
    public function delete(AttributeMapInterface $map, array $object, array $endpoint_object, bool $simulate = false): bool
    {
        throw new Exception\UnsupportedEndpointOperation('endpoint '.get_class($this).' does not support delete()');
    }

    /**
     * Get image contents.
     *
     * @param resource $stream
     */
    protected function scaleImage($stream): string
    {
        $imagick = new Imagick();
        $imagick->readImageFile($stream);

        if ($this->format !== null) {
            $imagick->setFormat($this->format);
        }

        if ($this->max_width !== 0 || $this->max_height !== 0) {
            $imagick->scaleImage($this->max_width, $this->max_height, true);
        }

        return $imagick->getImageBlob();
    }
}
