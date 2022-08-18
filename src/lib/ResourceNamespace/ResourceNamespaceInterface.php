<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2022 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\ResourceNamespace;

use Generator;
use Tubee\Collection\CollectionInterface;
use Tubee\Collection\Factory as CollectionFactory;
use Tubee\Resource\ResourceInterface;

interface ResourceNamespaceInterface extends ResourceInterface
{
    /**
     * Get identifier.
     */
    public function getIdentifier(): string;

    /**
     * Get collection factory.
     */
    public function getCollectionFactory(): CollectionFactory;

    /**
     * Check if namespace owns collection xy.
     */
    public function hasCollection(string $name): bool;

    /**
     * Get single collection.
     */
    public function getCollection(string $name): CollectionInterface;

    /**
     * Get related collections.
     */
    public function getCollections(array $collections = [], ?int $offset = null, ?int $limit = null): Generator;

    /**
     * Switch namespace.
     */
    public function switch(string $name): ResourceNamespaceInterface;
}
