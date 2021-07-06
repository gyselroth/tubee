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

class AddWorkgroupSmbStorage implements DeltaInterface
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
        $this->db->endpoints->updateMany([
            'data.storage.kind' => 'SmbStorage',
            'data.storage.workgroup' => ['$exists' => false],
        ], [
            '$set' => [
                'data.storage.workgroup' => null,
            ],
        ]);

        return true;
    }
}
