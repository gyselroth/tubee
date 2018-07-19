<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Workflow;

use MongoDB\BSON\UTCDateTime;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\DataObject\DataObjectInterface;
use Tubee\DataType\DataTypeInterface;
use Tubee\Endpoint\EndpointInterface;
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
     * Get name.
     */
    public function getName(): string;

    /**
     * Cleanup.
     */
    public function cleanup(DataObjectInterface $object, UTCDateTime $ts, bool $simulate = false): bool;

    /**
     * Import from endpoint.
     */
    public function import(DataTypeInterface $datatype, Iterable $object, UTCDateTime $ts, bool $simulate = false): bool;

    /**
     * Write to endpoint.
     *
     * @param iterable $object
     */
    public function export(DataObjectInterface $object, UTCDateTime $ts, bool $simulate = false): bool;
}
