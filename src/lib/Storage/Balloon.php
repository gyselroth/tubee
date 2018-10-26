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
use MongoDB\BSON\ObjectIdInterface;
use Psr\Log\LoggerInterface;
use Tubee\Endpoint\Balloon\ApiClient;

class Balloon implements StorageInterface
{
    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Balloon.
     *
     * @var ApiClient
     */
    protected $balloon;

    /**
     * Collection ID.
     *
     * @var string
     */
    protected $collection;

    /**
     * Init storage.
     */
    public function __construct(ApiClient $balloon, LoggerInterface $logger, ?ObjectIdInterface $collection = null)
    {
        $this->balloon = $balloon;
        $this->logger = $logger;
        $this->collection = $collection;
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

        $streams = [];
        foreach ($files as $file) {
            $this->logger->debug('open read stream from file ['.$file['id'].']', [
                'category' => get_class($this),
            ]);

            $stream = $this->balloon->openSocket('/api/v2/nodes/'.$file['id'].'/content');
            if ($stream === false) {
                throw new Exception\OpenStreamFailed('failed open read stream for '.$file['id']);
            }

            yield $file['name'] => $stream;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function openReadStream(string $file)
    {
        $path = '/api/v2/nodes/'.$file.'/content';

        $this->logger->debug('open read stream from file ['.$path.']', [
            'category' => get_class($this),
        ]);

        $stream = $this->balloon->openSocket($path);
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
        $this->logger->debug('open write stream for [php://temp]', [
            'category' => get_class($this),
        ]);

        $stream = fopen('php://temp', 'a+');

        if ($stream === false) {
            throw new Exception\OpenStreamFailed('failed open write stream for '.$file);
        }

        return $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function SyncWriteStream($stream, string $file): bool
    {
        $size = fstat($stream)['size'];

        if ($size === 0) {
            $this->logger->info('skip file of 0 bytes upload to balloon', [
                'category' => get_class($this),
            ]);

            return true;
        }

        $chunks = $size / 8388608;
        if ($chunks < 1) {
            $chunks = 1;
        }

        $this->logger->info('upload file to balloon as ['.$file.']', [
            'category' => get_class($this),
        ]);

        $query = [
            'collection' => $this->collection,
            'name' => $file,
            'size' => $size,
            'chunks' => $chunks,
        ];

        rewind($stream);

        $index = 0;
        $session = null;
        while (!feof($stream) && $index !== $chunks) {
            $buffer = fread($stream, 8388608);
            $query += [
                'index' => $index,
                'session' => $session,
            ];

            $this->logger->debug('upload file chunk ['.$index.'/'.$chunks.'] of total ['.$size.'] bytes to balloon', [
                'category' => get_class($this),
            ]);
            $result = $this->balloon->restCall('/api/v2/files/chunk', $query, 'PUT');

            ++$index;
            $session = $result['session'];
        }

        return true;
    }

    /**
     * Search balloon for files matching pattern.
     */
    protected function getFiles(string $pattern): array
    {
        $query = [
            'filter' => [
                'directory' => false,
                'name' => [
                    '$regex' => $pattern,
                ],
            ],
        ];

        if ($this->collection === null) {
            $url = '/api/v2/collections/children';
        } else {
            $url = '/api/v2/collections/'.$this->collection.'/children';
        }

        $result = $this->balloon->restCall($url, $query);

        return $result['data'];
    }
}
