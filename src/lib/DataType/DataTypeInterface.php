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
use Tubee\DataType\DataObject\DataObjectInterface;
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
     * Check if datatype has endpoint.
     */
    public function hasEndpoint(string $name): bool;

    /**
     * Inject endpoint.
     */
    public function injectEndpoint(EndpointInterface $endpoint, string $name): DataTypeInterface;

    /**
     * Get mandator.
     */
    public function getMandator(): MandatorInterface;

    /**
     * Get endpoint.
     */
    public function getEndpoint(string $name): EndpointInterface;

    /**
     * Get endpoints.
     */
    public function getEndpoints(Iterable $endpoints): array;

    /**
     * Get one.
     */
    public function getOne(Iterable $filter, bool $include_dataset = false, int $version = 0): DataObjectInterface;

    /**
     * Get all data objects.
     */
    public function getAll(Iterable $filter, bool $include_dataset = false, int $version = 0, ?int $offset = null, ?int $limit = null): Generator;

    /**
     * Write to destination endpoints.
     */
    public function export(UTCDateTime $timestamp, Iterable $filter, Iterable $endpoints, bool $simulate, bool $ignore): bool;

    /**
     * Import from source endpoints.
     */
    public function import(UTCDateTime $timestamp, Iterable $filter, Iterable $endpoints, bool $simulate, bool $ignore): bool;

    /**
     * Flush.
     */
    public function flush(bool $simulate = false): bool;

    /**
     * Create object.
     */
    public function create(Iterable $object, bool $simulate, array $endpoints): ObjectId;

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
    public function change(DataObjectInterface $object, Iterable $data, bool $simulate = false, array $endpoints = []): int;

    /**
     * Delete object.
     */
    public function delete(ObjectId $id, bool $simulate = false): bool;

    /**
     * Get source endpoints.
     */
    public function getSourceEndpoints(Iterable $endpoints = []): array;

    /**
     * Get destination endpoitns.
     *
     * @param array $endpoints
     */
    public function getDestinationEndpoints(Iterable $endpoints = []): array;

    /**
     * Get identifier.
     */
    public function getIdentifier(): string;

    /**
     * Get name.
     */
    public function getName(): string;
}
