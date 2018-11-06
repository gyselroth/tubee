<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Mandator;

use Generator;
use Tubee\DataType\DataTypeInterface;
use Tubee\DataType\Factory as DataTypeFactory;
use Tubee\Resource\ResourceInterface;

interface MandatorInterface extends ResourceInterface
{
    /**
     * Get identifier.
     */
    public function getIdentifier(): string;

    /**
     * Get datatype factory.
     */
    public function getDataTypeFactory(): DataTypeFactory;

    /**
     * Check if mandator owns datatype xy.
     */
    public function hasDataType(string $name): bool;

    /**
     * Get single datatype.
     */
    public function getDataType(string $name): DataTypeInterface;

    /**
     * Get related datatypes.
     */
    public function getDataTypes(array $datatypes = [], ?int $offset = null, ?int $limit = null): Generator;

    /**
     * Switch mandator.
     */
    public function switch(string $name): MandatorInterface;
}
