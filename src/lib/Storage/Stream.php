<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Storage;

use Generator;
use Psr\Log\LoggerInterface;

class Stream implements StorageInterface
{
    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Init storage.
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function openReadStreams(string $uri): Generator
    {
        $this->logger->debug('open read stream from uri ['.$uri.']', [
            'category' => get_class($this),
        ]);

        $stream = fopen($uri, 'r');
        if ($stream === false) {
            throw new Exception\OpenStreamFailed('failed open read stream for '.$uri);
        }

        yield $uri => $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function openReadStream(string $uri)
    {
        $this->logger->debug('open read stream from file ['.$uri.']', [
            'category' => get_class($this),
        ]);

        $stream = fopen($uri, 'r');
        if ($stream === false) {
            throw new Exception\OpenStreamFailed('failed open read stream for '.$uri);
        }

        return $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function openWriteStream(string $uri)
    {
        $this->logger->debug('open write stream for ['.$uri.']', [
            'category' => get_class($this),
        ]);

        $stream = fopen($uri, 'a+');
        if ($stream === false) {
            throw new Exception\OpenStreamFailed('failed open write stream for '.$uri);
        }

        return $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function SyncWriteStream($stream, string $uri): bool
    {
        return fclose($stream);
    }
}
