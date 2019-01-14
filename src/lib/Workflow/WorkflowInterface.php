<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Workflow;

use MongoDB\BSON\UTCDateTimeInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\DataObject\DataObjectInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\EndpointObject\EndpointObjectInterface;
use Tubee\Resource\ResourceInterface;

interface WorkflowInterface extends ResourceInterface
{
    /**
     * Object ensure states.
     */
    const ENSURE_EXISTS = 'exists';
    const ENSURE_LAST = 'last';
    const ENSURE_DISABLED = 'disabled';
    const ENSURE_ABSENT = 'absent';

    /**
     * Valid ensures.
     */
    const VALID_ENSURES = [
        self::ENSURE_EXISTS,
        self::ENSURE_LAST,
        self::ENSURE_DISABLED,
        self::ENSURE_ABSENT,
    ];

    /**
     * Get endpoint.
     */
    public function getEndpoint(): EndpointInterface;

    /**
     * Get attribute map.
     */
    public function getAttributeMap(): AttributeMapInterface;

    /**
     * Get identifier.
     */
    public function getIdentifier(): string;

    /**
     * Cleanup.
     */
    public function cleanup(DataObjectInterface $object, UTCDateTimeInterface $ts, bool $simulate = false): bool;

    /**
     * Import from endpoint.
     */
    public function import(CollectionInterface $collection, EndpointObjectInterface $object, UTCDateTimeInterface $ts, bool $simulate = false): bool;

    /**
     * Write to endpoint.
     *
     * @param iterable $object
     */
    public function export(DataObjectInterface $object, UTCDateTimeInterface $ts, bool $simulate = false): bool;
}
