<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use MongoDB\BSON\ObjectId;
use Psr\Http\Message\ServerRequestInterface;
use TaskScheduler\Scheduler;

class Job
{
    /**
     * Decorate.
     */
    public static function decorate(ObjectId $id, Scheduler $scheduler, ServerRequestInterface $request): array
    {
        $job = $scheduler->getJob($id);
        $job = array_intersect_key($job, array_flip(['at', 'interval', 'retry', 'retry_interval', 'created', 'status', 'data', 'class']));

        $data = [
            '_links' => [
                'self' => ['href' => (string) $request->getUri()],
            ],
            'kind' => 'Job',
            'id' => (string) $id,
        ];

        if (isset($job['created'])) {
            $job['created'] = $job['created']->toDateTime()->format('c');
        }

        if (isset($job['at'])) {
            $job['at'] = $job['at']->toDateTime()->format('c');
        }

        return array_merge($data, $job);
    }
}
