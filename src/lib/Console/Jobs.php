<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Console;

use GetOpt\GetOpt;
use Psr\Log\LoggerInterface;
use TaskScheduler\Queue;
use TaskScheduler\Scheduler;

class Jobs
{
    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Getopt.
     *
     * @var GetOpt
     */
    protected $getopt;

    /**
     * Task scheduler.
     *
     * @var Scheduler
     */
    protected $scheduler;

    /**
     * Task queue.
     *
     * @var Queue
     */
    protected $queue;

    /**
     * Constructor.
     */
    public function __construct(Scheduler $scheduler, Queue $queue, LoggerInterface $logger, GetOpt $getopt)
    {
        $this->scheduler = $scheduler;
        $this->queue = $queue;
        $this->logger = $logger;
        $this->getopt = $getopt;
    }

    /**
     * Fire up daemon.
     */
    public function __invoke(): bool
    {
        $this->logger->info('start taskscheduler queue listener', [
            'category' => get_class($this),
        ]);

        $this->queue->process();

        return true;
    }

    /**
     * Get options.
     */
    public static function getOptions(): array
    {
        return [];
    }

    /**
     * Get operands.
     */
    public static function getOperands(): array
    {
        return [];
    }
}
