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
use MongoDB\BSON\ObjectIdInterface;
use MongoDB\BSON\Regex;
use MongoDB\BSON\Timestamp;
use MongoDB\BSON\UTCDateTimeInterface;

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
     * Valid types.
     */
    const VALID_TYPES = [
        self::TYPE_STRING,
        self::TYPE_ARRAY,
        self::TYPE_INT,
        self::TYPE_FLOAT,
        self::TYPE_BOOL,
        self::TYPE_NULL,
    ];

    /**
     * Serializable class types.
     */
    const SERIALIZABLE_TYPES = [
        Binary::class,
        Decimal128::class,
        MaxKey::class,
        MinKey::class,
        ObjectIdInterface::class,
        Regex::class,
        Timestamp::class,
        UTCDateTimeInterface::class,
    ];

    /**
     * Get attribute map.
     */
    public function getMap(): Iterable;

    /**
     * Get attributes.
     */
    public function getAttributes(): array;

    /**
     * Map attributes.
     */
    public function map(Iterable $data, UTCDateTimeInterface $ts): array;

    /**
     * Create attribute diff.
     */
    public function getDiff(Iterable $mapped, Iterable $endpoint_object): array;
}
