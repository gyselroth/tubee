<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Resource;

use MongoDB\BSON\ObjectIdInterface;
use MongoDB\BSON\UTCDateTimeInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ResourceInterface
{
    /**
     * Get resource it.
     */
    public function getId(): ObjectIdInterface;

    /**
     * Get name.
     */
    public function getName(): string;

    /**
     * Convert resource to array.
     */
    public function toArray(): array;

    /**
     * Get data without metadata from a resource.
     */
    public function getData(): array;

    /**
     * Get resource secret mounts.
     */
    public function getSecrets(): array;

    /**
     * Get created timestamp.
     */
    public function getCreated(): UTCDateTimeInterface;

    /**
     * Get changed timestamp.
     */
    public function getChanged(): ?UTCDateTimeInterface;

    /**
     * Get deleted timestamp.
     */
    public function getDeleted(): ?UTCDateTimeInterface;

    /**
     * Get resource version.
     */
    public function getVersion(): int;

    /**
     * Decorate resource from server request.
     */
    public function decorate(ServerRequestInterface $request);
}
