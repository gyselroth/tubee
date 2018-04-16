<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use Psr\Log\LoggerInterface;
use Tubee\DataType\DataTypeInterface;
use Tubee\Storage\StorageInterface;

abstract class AbstractFile extends AbstractEndpoint
{
    /**
     * File.
     *
     * @var string
     */
    protected $file;

    /**
     * Storage.
     *
     * @var StorageInterface
     */
    protected $storage;

    /**
     * Resource.
     *
     * @var array
     */
    protected $resource = [];

    /**
     * Init endpoint.
     */
    public function __construct(string $name, string $type, string $file, StorageInterface $storage, DataTypeInterface $datatype, LoggerInterface $logger, ?Iterable $config = null)
    {
        $this->storage = $storage;
        $this->file = $file;
        parent::__construct($name, $type, $datatype, $logger, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function flush(bool $simulate = false): bool
    {
        $this->logger->info('flush file ['.$this->file.'] from endpoint ['.$this->name.']', [
            'category' => get_class($this),
        ]);

        if ($simulate === true) {
            return true;
        }

        foreach ($this->resource as $stream) {
            if (ftruncate($stream['stream'], 0) === false) {
                throw new Exception\WriteOperationFailed('failed flush file '.$this->file);
            }
        }

        return true;
    }
}
