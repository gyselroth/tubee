<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Workflow;

use MongoDB\BSON\UTCDateTimeInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\Helper;

class Map
{
    /**
     * Map.
     */
    public static function map(AttributeMapInterface $map, array $object, array $mongodb_object, UTCDateTimeInterface $ts): Iterable
    {
        $object = Helper::associativeArrayToPath($object);
        $mongodb_object = Helper::associativeArrayToPath($mongodb_object);

        foreach ($map->getMap() as $name => $value) {
            $name = $value['name'];

            $exists = isset($mongodb_object[$name]);
            if ($value['ensure'] === WorkflowInterface::ENSURE_EXISTS && $exists === true) {
                continue;
            }
            if (($value['ensure'] === WorkflowInterface::ENSURE_LAST || $value['ensure'] === WorkflowInterface::ENSURE_EXISTS) && isset($object[$name])) {
                $mongodb_object[$name] = $object[$name];
            } elseif ($value['ensure'] === WorkflowInterface::ENSURE_ABSENT && isset($mongodb_object[$name]) || !isset($object[$name]) && isset($mongodb_object[$name])) {
                unset($mongodb_object[$name]);
            }
        }

        return Helper::pathArrayToAssociative($mongodb_object);
    }
}
