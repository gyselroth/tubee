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

interface MandatorInterface
{
    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get identifier.
     *
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * Check if mandator has datatype.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasDataType(string $name): bool;

    /**
     * Inject datatype.
     *
     * @param DataTypeInterface $datatype
     * @param string            $name
     *
     * @return DataTypeInterface
     */
    public function injectDataType(DataTypeInterface $datatype, string $name): MandatorInterface;

    /**
     * Get datatype.
     *
     * @param string $name
     *
     * @return DataTypeInterface
     */
    public function getDataType(string $name): DataTypeInterface;

    /**
     * Get datatypes.
     *
     * @param iterable $datatypes
     *
     * @return array
     */
    public function getDataTypes(Iterable $datatypes): array;
}
