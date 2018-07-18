<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Resource;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;

class AbstractFactory
{
    /**
     * Add resource.
     */
    public function add(Collection $collection, array $resource): ObjectId
    {
        $resource += [
            'created' => new UTCDateTime(),
            'version' => 1,
        ];

        $result = $collection->insertOne($resource);

        return $result->getInsertedId();
    }
}
