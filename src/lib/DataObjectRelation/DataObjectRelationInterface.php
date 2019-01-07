<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\DataObjectRelation;

use Tubee\DataObject\DataObjectInterface;
use Tubee\Resource\ResourceInterface;

interface DataObjectRelationInterface extends ResourceInterface
{
    /**
     * Get relation context.
     */
    public function getContext(): array;

    /**
     * Get data object.
     */
    public function getDataObject(): DataObjectInterface;
}
