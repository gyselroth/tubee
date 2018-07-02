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
use TaskScheduler\AbstractJob;
use TaskScheduler\Scheduler;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Manager;

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
    public function __construct(Manager $manager, Scheduler $scheduler)
    {
        $this->manager = $manager;
        $this->scheduler = $scheduler;
    }

    /**
     * Start job.
     */
    public function start(): bool
    {
        $options = $this->getDefaults();

        foreach ($this->manager->getMandators($options['mandators']) as $mandator_name => $mandator) {
            foreach ($mandator->getDataTypes($options['datatypes']) as $dt_name => $datatype) {
                $res_endpoints = $datatype->getEndpoints($options['endpoints']);
                foreach ($res_endpoints as $ep_name => $endpoint) {
                    if ($options['loadbalance'] === true) {
                        $this->scheduler->addJob(self::class, [
                            'mandators' => [$mandator_name],
                            'endpoints' => [$ep_name],
                            'filter' => $options['filter'],
                            'loadbalance' => false,
                        ]);
                    } else {
                        if ($endpoint->getType() === EndpointInterface::TYPE_SOURCE) {
                            $datatype->import(new UTCDateTime(), $options['filter'], [$ep_name], $options['simulate'], $options['ignore']);
                        } elseif ($endpoint->getType() === EndpointInterface::TYPE_DESTINATION) {
                            $datatype->export(new UTCDateTime(), $options['filter'], [$ep_name], $options['simulate'], $options['ignore']);
                        }
                    }
                }
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
            'ignore' => false,
        ], $this->data);
    }
}
