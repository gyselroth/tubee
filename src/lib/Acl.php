<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use Generator;
use Micro\Auth\Identity;
use MongoDB\Database;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class Acl
{
    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Initialize.
     */
    public function __construct(Database $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    public function isAllowed(ServerRequestInterface $request, Identity $user)
    {
        $method = $request->getMethod();
    }

    public function filterOutput(ServerRequestInterface $request, Identity $user, Iterable $resources): Generator
    {
        $count = 0;
        foreach ($resources as $resource) {
            ++$count;
            yield $resource;
        }

        if ($resources instanceof Generator) {
            return $resources->getReturn();
        }

        return $count;
    }

    protected function getRole($user)
    {
    }
}
