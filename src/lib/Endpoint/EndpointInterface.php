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
use Tubee\Workflow\WorkflowInterface;

interface EndpointInterface
{
    /**
     * Types.
     */
    const TYPE_SOURCE = 'source';
    const TYPE_DESTINATION = 'destination';

    /**
     * Setup endpoint.
     *
     * @param bool $simulate
     *
     * @return EndpointInterface
     */
    public function setup(bool $simulate = false): EndpointInterface;

    /**
     * Shutdown endpoint.
     *
     * @param bool $simulate
     *
     * @return EndpointInterface
     */
    public function shutdown(bool $simulate = false): EndpointInterface;

    /**
     * Get type.
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Has workflow.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasWorkflow(string $name): bool;

    /**
     * inject workflow.
     *
     * @param WorkflowInterface $map
     * @param string            $name
     *
     * @return EndpointInterface
     */
    public function injectWorkflow(WorkflowInterface $workflow, string $name): EndpointInterface;

    /**
     * Get workflow.
     *
     * @param string $name
     *
     * @return WorkflowInterface
     */
    public function getWorkflow(string $name): WorkflowInterface;

    /**
     * Get workflows.
     *
     * @param iterable $workflows
     *
     * @return array
     */
    public function getWorkflows(Iterable $workflows = []): array;

    /**
     * Get object from endpoint.
     *
     * @param iterable $object
     * @param iterable $attributes
     *
     * @return iterable
     */
    public function getOne(Iterable $object, Iterable $attributes): Iterable;

    /**
     * Check if object does exists on endpoint.
     *
     * @param DataObjectInterface $object
     *
     * @return bool
     */
    public function exists(Iterable $object): bool;

    /**
     * Check if a flush is required.
     *
     * @return bool
     */
    public function flushRequired(): bool;

    /**
     * Flush endpoint.
     *
     * @return bool
     */
    public function flush(bool $simulate = false): bool;

    /**
     * Create object on endpoint.
     *
     * @param AttributeMapInterface $map
     * @param iterable              $object
     * @param bool                  $simulate
     *
     * @return bool
     */
    public function create(AttributeMapInterface $map, Iterable $object, bool $simulate = false): ?string;

    /**
     * Get diff for change.
     *
     * @param AttributeMapInterface $map
     * @param array                 $diff
     *
     * @return array
     */
    public function getDiff(AttributeMapInterface $map, array $diff): array;

    /**
     * Change object on endpoint.
     *
     * @param AttributeMapInterface $map
     * @param iterable              $object
     * @param iterable              $endpoint_object
     * @param bool                  $simulate
     *
     * @return bool
     */
    public function change(AttributeMapInterface $map, Iterable $diff, Iterable $object, Iterable $endpoint_object, bool $simulate = false): ?string;

    /**
     * Remove object from endpoint.
     *
     * @param AttributeMapInterface $map
     * @param iterable              $object
     * @param iterable              $endpoint_object
     * @param bool                  $simulate
     *
     * @return bool
     */
    public function delete(AttributeMapInterface $map, Iterable $object, Iterable $endpoint_object, bool $simulate = false): bool;

    /**
     * Read endpoint.
     *
     * @param mixed $filter
     *
     * @return Generator
     */
    public function getAll($filter): Generator;
}
