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

interface StorageInterface
{
    /**
     * Search files.
     */
    public function getFiles(string $pattern): array;

    /**
     * Open write stream.
     *
     *
     * @return array
     */
    public function openWriteStream(string $file);

    /**
     * Open read stream.
     *
     *
     * @return array
     */
    public function openReadStream(string $file);

    /**
     * Open multiple read streams from pattern.
     */
    public function openReadStreams(string $pattern): Generator;

    /**
     * Sync writeable stream.
     *
     * @param resource $stream
     */
    public function SyncWriteStream($stream, string $file): bool;
}
