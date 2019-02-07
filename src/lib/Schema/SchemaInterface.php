<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Schema;

interface SchemaInterface
{
    /**
     * Get Schema.
     */
    public function getSchema(): array;

    /**
     * Get attributes.
     */
    public function getAttributes(): array;

    /**
     * Validate.
     */
    public function validate(array $data): bool;
}
