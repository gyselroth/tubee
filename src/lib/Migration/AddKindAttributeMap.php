<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2021 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Migration;

use MongoDB\Database;

class AddKindAttributeMap implements DeltaInterface
{
    /**
     * Database.
     *
     * @var Database
     */
    protected $db;

    /**
     * Construct.
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritdoc}
     */
    public function start(): bool
    {
        foreach ($this->db->workflows->find() as $workflow) {
            $attributes = $workflow['data']['map'];
            foreach ($attributes as $key => &$attribute) {
                $attribute = $this->convertToKind($attribute);

                while (isset($attribute['unwind']) && is_array($attribute['unwind'])) {
                    $attribute = &$attribute['unwind'];
                    $attribute = $this->convertToKind($attribute);
                }
            }

            $this->db->workflows->updateOne(['_id' => $workflow['_id']], [
                '$set' => ['data.map' => $attributes],
            ]);
        }

        return true;
    }

    /**
     * Covnert old style declartion to kind/value.
     */
    protected function convertToKind(array $attribute): array
    {
        if (isset($attribute['script'])) {
            $attribute['kind'] = 'script';
            $attribute['value'] = $attribute['script'];
        } elseif (isset($attribute['from'])) {
            $attribute['kind'] = 'map';
            $attribute['value'] = $attribute['from'];
        } elseif (isset($attribute['value'])) {
            $attribute['kind'] = 'static';
            $attribute['value'] = $attribute['value'];
        } else {
            $attribute['kind'] = 'static';
            $attribute['value'] = null;
        }

        unset($attribute['script'], $attribute['from']);

        return $attribute;
    }
}
