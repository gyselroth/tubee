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
use MongoDB\BSON\ObjectId;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Yaml;
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
     *
     * @param App             $app
     * @param Async           $async
     * @param LoggerInterface $logger
     * @param GetOpt          $getopt
     */
    public function __construct(Scheduler $scheduler, Queue $queue, LoggerInterface $logger, GetOpt $getopt)
    {
        $this->scheduler = $scheduler;
        $this->queue = $queue;
        $this->logger = $logger;
        $this->getopt = $getopt;
    }

    /**
     * Set options.
     *
     * @return []
     */
    public static function getOptions(): array
    {
        return [];
    }

    /**
     * Set options.
     *
     * @return []
     */
    public static function getOperands(): array
    {
        return [
            \GetOpt\Operand::create('action', \GetOpt\Operand::REQUIRED),
            \GetOpt\Operand::create('mandator', \GetOpt\Operand::REQUIRED),
            \GetOpt\Operand::create('datatype', \GetOpt\Operand::REQUIRED),
            \GetOpt\Operand::create('id', \GetOpt\Operand::OPTIONAL),
        ];
    }

    public function help()
    {
        echo "delete\n";
        echo "Delete job by id\n\n";
        echo "listen\n";
        echo "Start listening for jobs asynchrounsly\n\n";
        echo "get\n";
        echo "Query active jobs\n\n";
        echo $this->getopt->getHelpText();
    }

    /**
     * Delete job by id.
     *
     * @return Jobs
     */
    public function delete(): Jobs
    {
        $id = new ObjectId($this->getopt->getOperand('id'));
        $this->scheduler->cancelJob($id);

        return $this;
    }

    /**
     * List active jobs.
     *
     * @return Jobs
     */
    public function get(): Jobs
    {
        foreach ($this->scheduler->getJobs() as $job) {
            echo Yaml::dump($job, 2, 4);
            echo "\n";
        }

        return $this;
    }

    /**
     * Fire up daemon.
     *
     * @return bool
     */
    public function listen(): bool
    {
        $this->logger->info('start taskscheduler queue listener', [
            'category' => get_class($this),
        ]);

        $this->queue->process();

        return true;
    }
}
