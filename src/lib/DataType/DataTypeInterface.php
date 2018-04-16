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

interface DataTypeInterface
{
    /**
     * Get dataset.
     *
     * @return iterable
     */
    public function getDataset(): array;

    /**
     * Check if datatype has endpoint.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasEndpoint(string $name): bool;

    /**
     * Inject endpoint.
     *
     * @param EndpointInterface $endpoint
     * @param string            $name
     *
     * @return DataTypeInterface
     */
    public function injectEndpoint(EndpointInterface $endpoint, string $name): DataTypeInterface;

    /**
     * Get mandator.
     *
     * @return MandatorInterface
     */
    public function getMandator(): MandatorInterface;

    /**
     * Get endpoint.
     *
     * @param string $name
     *
     * @return EndpointInterface
     */
    public function getEndpoint(string $name): EndpointInterface;

    /**
     * Get endpoints.
     *
     * @param iterable $endpoints
     *
     * @return array
     */
    public function getEndpoints(Iterable $endpoints): array;

    /**
     * Get one.
     *
     * @param iterable $filter
     * @param bool     $include_dataset
     * @param int      $version
     *
     * @return DataObjectInterface
     */
    public function getOne(Iterable $filter, bool $include_dataset = false, int $version = 0): DataObjectInterface;

    /**
     * Get all data objects.
     *
     * @param iterable $filter
     * @param bool     $include_dataset
     * @param int      $version
     *
     * @return Generator
     */
    public function getAll(Iterable $filter, bool $include_dataset = false, int $version = 0): Generator;

    /**
     * Write to destination endpoints.
     *
     * @param UTCDateTime $timestamp
     * @param iterable    $filter
     * @param iterable    $endpoints
     * @param bool        $simulate
     * @param bool        $ignore
     *
     * @return bool
     */
    public function export(UTCDateTime $timestamp, Iterable $filter, Iterable $endpoints, bool $simulate, bool $ignore): bool;

    /**
     * Import from source endpoints.
     *
     * @param UTCDateTime $timestamp
     * @param iterable    $filter
     * @param iterable    $endpoints
     * @param bool        $simulate
     * @param bool        $ignore
     *
     * @return bool
     */
    public function import(UTCDateTime $timestamp, Iterable $filter, Iterable $endpoints, bool $simulate, bool $ignore): bool;

    /**
     * Flush.
     *
     * @param bool $simulate
     */
    public function flush(bool $simulate = false): bool;

    /**
     * Create object.
     *
     * @param iterable $object
     * @param bool     $simulate
     * @param array    $endpoints
     *
     * @return ObjectId
     */
    public function create(Iterable $object, bool $simulate, array $endpoints): ObjectId;

    /**
     * Enable object.
     *
     * @param ObjectId $id
     * @param bool     $simulate
     *
     * @return bool
     */
    public function enable(ObjectId $id, bool $simulate = false): bool;

    /**
     * Disable object.
     *
     * @param ObjectId $id
     * @param bool     $simulate
     * @param array    $endpoints
     *
     * @return bool
     */
    public function disable(ObjectId $id, bool $simulate = false): bool;

    /**
     * Change object.
     *
     * @param DataObjectInterface $object
     * @param iterable            $new
     * @param bool                $simulate
     * @param array               $endpoints
     *
     * @return int
     */
    public function change(DataObjectInterface $object, Iterable $data, bool $simulate = false, array $endpoints = []): int;

    /**
     * Delete object.
     *
     * @param iterable $filter
     * @param bool     $simulate
     */
    public function delete(ObjectId $id, bool $simulate = false): bool;

    /**
     * Get source endpoints.
     *
     * @param iterable $endpoints
     *
     * @return array
     */
    public function getSourceEndpoints(Iterable $endpoints = []): array;

    /**
     * Get destination endpoitns.
     *
     * @param array $endpoints
     *
     * @return array
     */
    public function getDestinationEndpoints(Iterable $endpoints = []): array;

    /**
     * Get identifier.
     *
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string;
}
