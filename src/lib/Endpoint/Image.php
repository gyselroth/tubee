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
use Tubee\Storage\StorageInterface;

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
    public function __construct(string $name, string $type, string $file, StorageInterface $storage, DataTypeInterface $datatype, LoggerInterface $logger, ?Iterable $config = null, ?Iterable $image_options = null)
    {
        $this->setImageOptions($image_options);
        parent::__construct($name, $type, $file, $storage, $datatype, $logger, $config);
    }

    /**
     * Set image options.
     */
    public function setImageOptions(?Iterable $config = null): EndpointInterface
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
    public function getOne(Iterable $object, Iterable $attributes = []): Iterable
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function exists(Iterable $object): bool
    {
        throw new Exception\UnsupportedEndpointOperation('endpoint '.get_class($this).' does not support exists()');
    }

    /**
     * {@inheritdoc}
     */
    public function getAll($filter = []): Generator
    {
        foreach ($this->storage->openReadStreams($this->file) as $name => $stream) {
            yield [
                'name' => $name,
                'content' => $this->scaleImage($stream),
            ];

            fclose($stream);
        }
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
    public function create(AttributeMapInterface $map, Iterable $object, bool $simulate = false): ?string
    {
        throw new Exception\UnsupportedEndpointOperation('endpoint '.get_class($this).' does not support create()');
    }

    /**
     * {@inheritdoc}
     */
    public function change(AttributeMapInterface $map, Iterable $diff, Iterable $object, Iterable $endpoint_object, bool $simulate = false): ?string
    {
        throw new Exception\UnsupportedEndpointOperation('endpoint '.get_class($this).' does not support change()');
    }

    /**
     * {@inheritdoc}
     */
    public function delete(AttributeMapInterface $map, Iterable $object, Iterable $endpoint_object, bool $simulate = false): bool
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
