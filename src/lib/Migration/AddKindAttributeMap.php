<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
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
            foreach ($attributes as $key => $attribute) {
                if ($attribute['script'] !== null) {
                    $attribute['kind'] = 'script';
                    $attribute['value'] = $attribute['script'];
                } elseif ($attribute['from'] !== null) {
                    $attribute['kind'] = 'from';
                    $attribute['value'] = $attribute['from'];
                } else {
                    $attribute['kind'] = 'static';
                    $attribute['value'] = $attribute['value'];
                }

                unset($attribute['script'], $attribute['from']);
            }

            $this->db->workflows->updateOne(['_id' => $workflow['_id']], [
                '$set' => ['data.map' => $attributes],
            ]);
        }

        return true;
    }
}
