<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\AttributeMap;

use MongoDB\BSON\Binary;
use MongoDB\BSON\Decimal128;
use MongoDB\BSON\MaxKey;
use MongoDB\BSON\MinKey;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use MongoDB\BSON\Timestamp;
use MongoDB\BSON\UTCDateTime;

interface AttributeMapInterface
{
    /**
     * Ensure states.
     */
    const ENSURE_EXISTS = 'exists';
    const ENSURE_LAST = 'last';
    const ENSURE_ABSENT = 'absent';
    const ENSURE_MERGE = 'merge';

    /**
     * Diff actions.
     */
    const ACTION_REPLACE = 0;
    const ACTION_REMOVE = 1;
    const ACTION_ADD = 2;

    /**
     * Types.
     */
    const TYPE_STRING = 'string';
    const TYPE_ARRAY = 'array';
    const TYPE_INT = 'int';
    const TYPE_FLOAT = 'float';
    const TYPE_BOOL = 'bool';
    const TYPE_NULL = 'null';

    /**
     * Serializable class types.
     */
    const SERIALIZABLE_TYPES = [
        Binary::class,
        Decimal128::class,
        MaxKey::class,
        MinKey::class,
        ObjectId::class,
        Regex::class,
        Timestamp::class,
        UTCDateTime::class,
    ];

    /**
     * Get attribute map.
     *
     * @return iterable
     */
    public function getMap(): Iterable;

    /**
     * Get attributes.
     *
     * @return array
     */
    public function getAttributes(): array;

    /**
     * Map attributes.
     *
     * @param iterable    $data
     * @param UTCDateTime $ts
     *
     * @return array
     */
    public function map(Iterable $data, UTCDateTime $ts): array;

    /**
     * Create attribute diff.
     *
     * @param iterable $mapped
     * @param iterable $endpoint_object
     *
     * @return array
     */
    public function getDiff(Iterable $mapped, Iterable $endpoint_object): array;
}
