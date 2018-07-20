<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Async;

use MongoDB\BSON\UTCDateTime;
use Monolog\Handler\MongoDBHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use TaskScheduler\AbstractJob;
use TaskScheduler\Scheduler;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Mandator\Factory as MandatorFactory;

class Sync extends AbstractJob
{
    /**
     * Manager.
     *
     * @var Manager
     */
    protected $manager;

    /**
     * Scheduler.
     *
     * @var Scheduler
     */
    protected $scheduler;

    /**
     * Sync.
     */
    public function __construct(MandatorFactory $mandator, Scheduler $scheduler, LoggerInterface $logger)
    {
        $this->mandator = $mandator;
        $this->scheduler = $scheduler;
        $this->logger = $logger;
    }

    /**
     * Start job.
     */
    public function start(): bool
    {
        $options = $this->getDefaults();
        $filter = !empty($options['mandators']) ? ['name' => ['$in' => $options['mandators']]] : [];
        foreach ($this->mandator->getAll($filter) as $mandator_name => $mandator) {
            $filter = !empty($options['datatypes']) ? ['name' => ['$in' => $options['datatypes']]] : [];
            foreach ($mandator->getDataTypes($filter) as $dt_name => $datatype) {
                $filter = !empty($options['endpoints']) ? ['name' => ['$in' => $options['endpoints']]] : [];
                foreach ($datatype->getEndpoints($filter) as $ep_name => $endpoint) {
                    if ($options['loadbalance'] === true) {
                        $id = $this->scheduler->addJob(self::class, [
                            'mandators' => [$mandator_name],
                            'endpoints' => [$ep_name],
                            'filter' => $options['filter'],
                            'loadbalance' => false,
                            'ignore' => $options['ignore'],
                            'simulate' => $options['simulate'],
                            'log_level' => $options['log_level'],
                        ]);
                    } else {
                        $this->setupLogger($options['log_level'], [
                            'job' => (string) $this->getId(),
                            'mandator' => $mandator_name,
                            'datatype' => $dt_name,
                            'endpoint' => $ep_name,
                        ]);

                        if ($endpoint->getType() === EndpointInterface::TYPE_SOURCE) {
                            $datatype->import(new UTCDateTime(), $options['filter'], ['name' => $ep_name], $options['simulate'], $options['ignore']);
                        } elseif ($endpoint->getType() === EndpointInterface::TYPE_DESTINATION) {
                            $datatype->export(new UTCDateTime(), $options['filter'], ['name' => $ep_name], $options['simulate'], $options['ignore']);
                        }

                        $this->logger->popProcessor();
                    }
                }
            }
        }

        return true;
    }

    /**
     * Set logger level.
     */
    protected function setupLogger(int $level, array $context): bool
    {
        foreach ($this->logger->getHandlers() as $handler) {
            if ($handler instanceof MongoDBHandler) {
                $handler->setLevel($level);

                $this->logger->pushProcessor(function ($record) use ($context) {
                    $record['context'] = array_merge($record['context'], $context);

                    return $record;
                });
            }
        }

        return true;
    }

    /**
     * Get job defaults.
     */
    protected function getDefaults(): array
    {
        return array_merge([
            'mandators' => [],
            'datatypes' => [],
            'endpoints' => [],
            'filter' => [],
            'loadbalance' => true,
            'simulate' => false,
            'log_level' => Logger::ERROR,
            'ignore' => false,
        ], $this->data);
    }
}
