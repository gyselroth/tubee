<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2021 gyselroth GmbH (https://gyselroth.com)
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
        'StreamStorage' => Stream::class,
        'LocalFilesystemStorage' => LocalFilesystem::class,
        'BalloonStorage' => Balloon::class,
        'SmbStorage' => Smb::class,
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
