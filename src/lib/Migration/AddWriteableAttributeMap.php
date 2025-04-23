<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Migration;

use MongoDB\Database;

class AddWriteableAttributeMap implements DeltaInterface
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
                $attributes[$key]['writeonly'] = false;
            }

            $this->db->workflows->updateOne(['_id' => $workflow['_id']], [
                '$set' => ['data.map' => $attributes],
            ]);
        }

        return true;
    }
}
