<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use Generator;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\EndpointObject\EndpointObjectInterface;
use Tubee\Resource\ResourceInterface;
use Tubee\Workflow\WorkflowInterface;

interface EndpointInterface extends ResourceInterface
{
    /**
     * Types.
     */
    const TYPE_SOURCE = 'source';
    const TYPE_DESTINATION = 'destination';
    const TYPE_BROWSE = 'browse';
    const TYPE_BIDIRECTIONAL = 'bidirectional';
    const VALID_TYPES = [
        self::TYPE_SOURCE,
        self::TYPE_DESTINATION,
        self::TYPE_BROWSE,
        self::TYPE_BIDIRECTIONAL,
    ];

    /**
     * Endpoint class map.
     */
    const ENDPOINT_MAP = [
        Ldap::KIND => Ldap::class,
        Pdo::KIND => Pdo::class,
        OdataRest::KIND => OdataRest::class,
        Balloon::KIND => Balloon::class,
        Csv::KIND => Csv::class,
        Json::KIND => Json::class,
        Xml::KIND => Xml::class,
        Mongodb::KIND => Mongodb::class,
        Mysql::KIND => Mysql::class,
        Image::KIND => Image::class,
        Ucs::KIND => Ucs::class,
        MicrosoftGraph::KIND => MicrosoftGraph::class,
    ];

    /**
     * Setup endpoint.
     */
    public function setup(bool $simulate = false): EndpointInterface;

    /**
     * Get identifier.
     */
    public function getIdentifier(): string;

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
    public function getOne(array $object, array $attributes): EndpointObjectInterface;

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
     */
    public function create(AttributeMapInterface $map, array $object, bool $simulate = false): ?string;

    /**
     * Get diff for change.
     */
    public function getDiff(AttributeMapInterface $map, array $diff): array;

    /**
     * Change object on endpoint.
     */
    public function change(AttributeMapInterface $map, array $diff, array $object, EndpointObjectInterface $endpoint_object, bool $simulate = false): ?string;

    /**
     * Remove object from endpoint.
     */
    public function delete(AttributeMapInterface $map, array $object, EndpointObjectInterface $endpoint_object, bool $simulate = false): bool;

    /**
     * Count EndpointObjects.
     */
    public function count(?array $query = null): int;

    /**
     * Read endpoint.
     */
    public function getAll(?array $query = null): Generator;

    /**
     * Transform mongodb like query into endpoints native query language.
     */
    public function transformQuery(?array $query = null);
}
