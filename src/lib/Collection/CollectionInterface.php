<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Collection;

use Generator;
use MongoDB\BSON\ObjectIdInterface;
use Tubee\DataObject\DataObjectInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Resource\ResourceInterface;
use Tubee\ResourceNamespace\ResourceNamespaceInterface;

interface CollectionInterface extends ResourceInterface
{
    /**
     * Get namespace.
     */
    public function getResourceNamespace(): ResourceNamespaceInterface;

    /**
     * Has endpoint.
     */
    public function hasEndpoint(string $name): bool;

    /**
     * Get endpoint.
     */
    public function getEndpoint(string $name): EndpointInterface;

    /**
     * Get Endpoints.
     */
    public function getEndpoints(array $endpoints = [], ?int $offset = null, ?int $limit = null): Generator;

    /**
     * Get one.
     */
    public function getObject(array $filter, bool $include_dataset = false, int $version = 0): DataObjectInterface;

    /**
     * Get all data objects.
     */
    public function getObjects(array $filter, bool $include_dataset = false, ?int $offset = null, ?int $limit = null): Generator;

    /**
     * Flush.
     */
    public function flush(bool $simulate = false): bool;

    /**
     * Create object.
     */
    public function createObject(array $object, bool $simulate, array $endpoints): ObjectIdInterface;

    /**
     * Change object.
     */
    public function changeObject(DataObjectInterface $object, array $data, bool $simulate = false, ?array $endpoints = null): bool;

    /**
     * Delete object.
     */
    public function deleteObject(ObjectIdInterface $id, bool $simulate = false): bool;

    /**
     * Get identifier.
     */
    public function getIdentifier(): string;
}
