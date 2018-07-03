<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Mandator;

use Tubee\DataType\DataTypeInterface;
use Tubee\Resource\ResourceInterface;

interface MandatorInterface extends ResourceInterface
{
    /**
     * Get name.
     */
    public function getName(): string;

    /**
     * Get identifier.
     */
    public function getIdentifier(): string;

    /**
     * Check if mandator has datatype.
     */
    public function hasDataType(string $name): bool;

    /**
     * Inject datatype.
     *
     *
     * @return DataTypeInterface
     */
    public function injectDataType(DataTypeInterface $datatype, string $name): MandatorInterface;

    /**
     * Get datatype.
     */
    public function getDataType(string $name): DataTypeInterface;

    /**
     * Get datatypes.
     */
    public function getDataTypes(Iterable $datatypes): array;
}
