<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2022 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\DataObject;

use Tubee\Collection\CollectionInterface;
use Tubee\Resource\ResourceInterface;

interface DataObjectInterface extends ResourceInterface
{
    /**
     * Get data type.
     */
    public function getCollection(): CollectionInterface;

    /**
     * Get endpoints.
     */
    public function getEndpoints(): array;
}
