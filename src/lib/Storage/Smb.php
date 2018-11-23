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
use Icewind\SMB\IShare;
use Icewind\SMB\NativeServer;
use Psr\Log\LoggerInterface;

class Smb implements StorageInterface
{
    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * SMB server.
     *
     * @var NativeServer
     */
    protected $server;

    /**
     * Share.
     *
     * @var IShare
     */
    protected $share;

    /**
     * Root.
     *
     * @var string
     */
    protected $root;

    /**
     * Init storage.
     */
    public function __construct(NativeServer $server, LoggerInterface $logger, string $share, string $root = '/')
    {
        $this->server = $server;
        $this->logger = $logger;
        $this->share = $server->getShare($share);
        $this->root = $root;
    }

    /**
     * {@inheritdoc}
     */
    public function openReadStreams(string $pattern): Generator
    {
        $streams = [];
        $files = $this->getFiles($pattern);
        if (count($files) === 0) {
            throw new Exception\NoFilesFound('no files found for '.$pattern);
        }

        foreach ($files as $file) {
            $this->logger->debug('open read stream from file ['.$file.']', [
                'category' => get_class($this),
            ]);

            $stream = $this->share->read($file);
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

        $stream = $this->share->read($path);
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

        $stream = $this->share->write($path);
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

    /**
     * Search smb share for files matching pattern.
     */
    protected function getFiles(string $pattern): array
    {
        $result = [];
        $base = dirname($pattern);
        $path = $this->root.DIRECTORY_SEPARATOR.$base;
        $content = $this->share->dir($path);
        $pattern = basename($pattern);

        foreach ($content as $node) {
            if (preg_match('#'.$pattern.'#', $node->getName())) {
                $result[] = $path.DIRECTORY_SEPARATOR.$node->getName();
            }
        }

        return $result;
    }
}
