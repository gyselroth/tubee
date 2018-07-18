<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\DataType;

use Generator;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Tubee\DataObject\DataObjectInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Mandator;
use Tubee\Mandator\MandatorInterface;
use Tubee\Resource\ResourceInterface;

interface DataTypeInterface extends ResourceInterface
{
    /**
     * Get dataset.
     *
     * @return iterable
     */
    public function getDataset(): array;

    /**
     * Get mandator.
     */
    public function getMandator(): MandatorInterface;

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
    public function getOne(array $filter, bool $include_dataset = false, int $version = 0): DataObjectInterface;

    /**
     * Get all data objects.
     */
    public function getAll(array $filter, bool $include_dataset = false, ?int $offset = null, ?int $limit = null): Generator;

    /**
     * Write to destination endpoints.
     */
    public function export(UTCDateTime $timestamp, array $filter, array $endpoints, bool $simulate, bool $ignore): bool;

    /**
     * Import from source endpoints.
     */
    public function import(UTCDateTime $timestamp, array $filter, array $endpoints, bool $simulate, bool $ignore): bool;

    /**
     * Flush.
     */
    public function flush(bool $simulate = false): bool;

    /**
     * Create object.
     */
    public function create(array $object, bool $simulate, array $endpoints): ObjectId;

    /**
     * Enable object.
     */
    public function enable(ObjectId $id, bool $simulate = false): bool;

    /**
     * Disable object.
     */
    public function disable(ObjectId $id, bool $simulate = false): bool;

    /**
     * Change object.
     */
    public function change(DataObjectInterface $object, array $data, bool $simulate = false, array $endpoints = []): int;

    /**
     * Delete object.
     */
    public function delete(ObjectId $id, bool $simulate = false): bool;

    /**
     * Get identifier.
     */
    public function getIdentifier(): string;

    /**
     * Get name.
     */
    public function getName(): string;
}
