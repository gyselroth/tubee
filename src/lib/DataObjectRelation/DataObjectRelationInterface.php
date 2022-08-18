<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2022 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\DataObjectRelation;

use Tubee\Collection;
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
    public function getDataObject(): ?DataObjectInterface;

    /**
     * Get data object by relation.
     */
    public function getDataObjectByRelation(DataObjectRelationInterface $relation, Collection $collection): ?DataObjectInterface;
}
