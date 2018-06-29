<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Storage;

use Generator;
use Psr\Log\LoggerInterface;

class LocalFilesystem implements StorageInterface
{
    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Root dir.
     *
     * @var string
     */
    protected $root = '/';

    /**
     * Init storage.
     */
    public function __construct(string $root, LoggerInterface $logger)
    {
        $this->root = $root;
        $this->logger = $logger;

        if (!is_readable($root) || !is_dir($root)) {
            throw new Exception\RootDirectoryNotFound('given root directory '.$root.' is not readable');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFiles(string $pattern): array
    {
        return glob($this->root.DIRECTORY_SEPARATOR.$pattern, GLOB_BRACE);
    }

    /**
     * {@inheritdoc}
     */
    public function openReadStreams(string $pattern): Generator
    {
        $files = $this->getFiles($pattern);
        if (count($files) === 0) {
            throw new Exception\NoFilesFound('no files found for '.$pattern);
        }

        foreach ($files as $file) {
            $this->logger->debug('open read stream from file ['.$file.']', [
                'category' => get_class($this),
            ]);

            $stream = fopen($file, 'r');
            if ($stream === false) {
                throw new Exception\OpenStreamFailed('failed open read stream for '.$file);
            }

            yield $file => $stream;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function openReadStream(string $file)
    {
        $path = $this->root.DIRECTORY_SEPARATOR.$file;

        $this->logger->debug('open read stream from file ['.$path.']', [
            'category' => get_class($this),
        ]);

        $stream = fopen($path, 'r');
        if ($stream === false) {
            throw new Exception\OpenStreamFailed('failed open read stream for '.$path);
        }

        return $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function openWriteStream(string $file)
    {
        $path = $this->root.DIRECTORY_SEPARATOR.$file;

        $this->logger->debug('open write stream from file ['.$path.']', [
            'category' => get_class($this),
        ]);

        $stream = fopen($path, 'a+');
        if ($stream === false) {
            throw new Exception\OpenStreamFailed('failed open write stream for '.$path);
        }

        return $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function SyncWriteStream($stream, string $file): bool
    {
        return true;
    }
}
