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
     * StorageMap.
     */
    const STORAGE_MAP = [
        'Stream' => Stream::class,
        'LocalFilesystem' => LocalFilesystem::class,
        'Balloon' => Balloon::class,
        'Smb' => Smb::class,
    ];

    /**
     * Open write stream.
     */
    public function openWriteStream(string $file);

    /**
     * Open read stream.
     */
    public function openReadStream(string $file);

    /**
     * Open multiple read streams from pattern.
     */
    public function openReadStreams(string $pattern): Generator;

    /**
     * Sync writeable stream.
     */
    public function SyncWriteStream($stream, string $file): bool;
}
