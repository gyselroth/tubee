<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Resource;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Psr\Http\Message\ServerRequestInterface;

interface ResourceInterface
{
    /**
     * Get resource it.
     */
    public function getId(): ObjectId;

    /**
     * Convert resource to array.
     */
    public function toArray(): array;

    /**
     * Get data without metadata from a resource.
     */
    public function getData(): array;

    /**
     * Get created timestamp.
     */
    public function getCreated(): UTCDateTime;

    /**
     * Get changed timestamp.
     */
    public function getChanged(): ?UTCDateTime;

    /**
     * Get deleted timestamp.
     */
    public function getDeleted(): ?UTCDateTime;

    /**
     * Get resource version.
     */
    public function getVersion(): int;

    /**
     * Decorate resource from server request.
     */
    public function decorate(ServerRequestInterface $request);
}
