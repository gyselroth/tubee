<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2022 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use Psr\Log\LoggerInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Storage\StorageInterface;
use Tubee\Workflow\Factory as WorkflowFactory;

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
     * Files.
     *
     * @var array
     */
    protected $files = [];

    /**
     * Writable stream (destination endpoint).
     *
     * @var resource
     */
    protected $writable;

    /**
     * Init endpoint.
     */
    public function __construct(string $name, string $type, string $file, StorageInterface $storage, CollectionInterface $collection, WorkflowFactory $workflow, LoggerInterface $logger, ?iterable $resource = [])
    {
        $this->storage = $storage;
        $this->file = $file;
        parent::__construct($name, $type, $collection, $workflow, $logger, $resource);
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

        if (ftruncate($this->writable, 0) === false) {
            throw new Exception\WriteOperationFailed('failed flush file '.$this->file);
        }

        return true;
    }
}
