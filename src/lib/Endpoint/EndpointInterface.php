<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use Generator;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\Resource\ResourceInterface;
use Tubee\Workflow\WorkflowInterface;

interface EndpointInterface extends ResourceInterface
{
    /**
     * Types.
     */
    const TYPE_SOURCE = 'source';
    const TYPE_DESTINATION = 'destination';

    /**
     * Setup endpoint.
     */
    public function setup(bool $simulate = false): EndpointInterface;

    /**
     * Shutdown endpoint.
     */
    public function shutdown(bool $simulate = false): EndpointInterface;

    /**
     * Get type.
     */
    public function getType(): string;

    /**
     * Has workflow.
     */
    public function hasWorkflow(string $name): bool;

    /**
     * Get workflow.
     */
    public function getWorkflow(string $name): WorkflowInterface;

    /**
     * Get workflows.
     */
    public function getWorkflows(array $workflows = [], ?int $offset = null, ?int $limit = null): Generator;

    /**
     * Get object from endpoint.
     */
    public function getOne(array $object, array $attributes): array;

    /**
     * Check if object does exists on endpoint.
     *
     * @param DataObjectInterface $object
     */
    public function exists(array $object): bool;

    /**
     * Check if a flush is required.
     */
    public function flushRequired(): bool;

    /**
     * Flush endpoint.
     */
    public function flush(bool $simulate = false): bool;

    /**
     * Create object on endpoint.
     *
     *
     * @return bool
     */
    public function create(AttributeMapInterface $map, array $object, bool $simulate = false): ?string;

    /**
     * Get diff for change.
     */
    public function getDiff(AttributeMapInterface $map, array $diff): array;

    /**
     * Change object on endpoint.
     *
     *
     * @return bool
     */
    public function change(AttributeMapInterface $map, array $diff, array $object, array $endpoint_object, bool $simulate = false): ?string;

    /**
     * Remove object from endpoint.
     */
    public function delete(AttributeMapInterface $map, array $object, array $endpoint_object, bool $simulate = false): bool;

    /**
     * Read endpoint.
     */
    public function getAll($filter): Generator;
}
