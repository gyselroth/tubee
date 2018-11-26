<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\AttributeMap;

interface AttributeMapInterface
{
    /**
     * Ensure states.
     */
    const ENSURE_EXISTS = 'exists';
    const ENSURE_LAST = 'last';
    const ENSURE_ABSENT = 'absent';
    const ENSURE_MERGE = 'merge';

    const VALID_ENSURES = [
        self::ENSURE_EXISTS,
        self::ENSURE_LAST,
        self::ENSURE_ABSENT,
        self::ENSURE_MERGE,
    ];

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
    const TYPE_BINARY = 'binary';

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
        self::TYPE_BINARY,
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
    public function map(array $data): array;

    /**
     * Create attribute diff.
     */
    public function getDiff(array $mapped, array $endpoint_object): array;
}
