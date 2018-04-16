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

interface WorkflowInterface
{
    /**
     * Object ensure states.
     */
    const ENSURE_EXISTS = 'exists';
    const ENSURE_LAST = 'last';
    const ENSURE_DISABLED = 'disabled';
    const ENSURE_ABSENT = 'absent';

    /**
     * Get endpoint.
     *
     * @return EndpointInterface
     */
    public function getEndpoint(): EndpointInterface;

    /**
     * Get attribute map.
     *
     * @return AttributeMapInterface
     */
    public function getAttributeMap(): AttributeMapInterface;

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

    /**
     * Cleanup.
     *
     * @param DataObjectInterface $object
     * @param UTCDateTime         $ts
     * @param bool                $simulate
     *
     * @return bool
     */
    public function cleanup(DataObjectInterface $object, UTCDateTime $ts, bool $simulate = false): bool;

    /**
     * Import from endpoint.
     *
     * @param iterable    $object
     * @param UTCDateTime $ts
     * @param bool        $simulate
     *
     * @return bool
     */
    public function import(DataTypeInterface $datatype, Iterable $object, UTCDateTime $ts, bool $simulate = false): bool;

    /**
     * Write to endpoint.
     *
     * @param iterable    $object
     * @param UTCDateTime $ts
     * @param bool        $simulate
     *
     * @return bool
     */
    public function export(DataObjectInterface $object, UTCDateTime $ts, bool $simulate = false): bool;
}
