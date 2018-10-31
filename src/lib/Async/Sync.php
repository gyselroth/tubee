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
use Tubee\DataType\DataTypeInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Job\Validator as JobValidator;
use Tubee\Mandator\Factory as MandatorFactory;
use Zend\Mail\Message;

class Sync extends AbstractJob
{
    /**
     * Mandator factory.
     *
     * @var MandatorFactory
     */
    protected $mandator_factory;

    /**
     * Scheduler.
     *
     * @var Scheduler
     */
    protected $scheduler;

    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Error count.
     *
     * @var int
     */
    protected $error_count = 0;

    /**
     * Start timestamp.
     *
     * @var UTCDateTime
     */
    protected $timestamp;

    /**
     * Sync.
     */
    public function __construct(MandatorFactory $mandator_factory, Scheduler $scheduler, LoggerInterface $logger)
    {
        $this->mandator_factory = $mandator_factory;
        $this->scheduler = $scheduler;
        $this->logger = $logger;
        $this->timestamp = new UTCDateTime();
    }

    /**
     * Start job.
     */
    public function start(): bool
    {
        $filter = !empty($this->data['mandators']) ? ['name' => ['$in' => $this->data['mandators']]] : [];
        foreach ($this->mandator_factory->getAll($filter) as $mandator_name => $mandator) {
            $filter = !empty($this->data['datatypes']) ? ['name' => ['$in' => $this->data['datatypes']]] : [];
            foreach ($mandator->getDataTypes($filter) as $dt_name => $datatype) {
                $filter = !empty($this->data['endpoints']) ? ['name' => ['$in' => $this->data['endpoints']]] : [];
                foreach ($datatype->getEndpoints($filter) as $ep_name => $endpoint) {
                    if ($this->data['loadbalance'] === true) {
                        $data = $this->data;
                        $data = array_merge($data, [
                            'mandators' => [$mandator_name],
                            'endpoints' => [$ep_name],
                            'loadbalance' => false,
                        ]);

                        $id = $this->scheduler->addJob(self::class, $data);
                    } else {
                        $this->setupLogger(JobValidator::LOG_LEVELS[$this->data['log_level']], [
                            'job' => (string) $this->data['job'],
                            'process' => (string) $this->getId(),
                            'start' => $this->timestamp,
                            'mandator' => $mandator_name,
                            'datatype' => $dt_name,
                            'endpoint' => $ep_name,
                        ]);

                        if ($endpoint->getType() === EndpointInterface::TYPE_SOURCE) {
                            $this->import($datatype, $this->data['filter'], ['name' => $ep_name], $this->data['simulate'], $this->data['ignore']);
                        } elseif ($endpoint->getType() === EndpointInterface::TYPE_DESTINATION) {
                            $this->export($datatype, $this->data['filter'], ['name' => $ep_name], $this->data['simulate'], $this->data['ignore']);
                        }

                        $this->logger->popProcessor();
                        $this->notify();
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
     * {@inheritdoc}
     */
    protected function export(DataTypeInterface $datatype, array $filter = [], array $endpoints = [], bool $simulate = false, bool $ignore = false): bool
    {
        $this->logger->info('start export to destination endpoints from data type ['.$datatype->getIdentifier().']', [
            'category' => get_class($this),
        ]);

        $endpoints = iterator_to_array($datatype->getDestinationEndpoints($endpoints));

        foreach ($endpoints as $ep) {
            if ($ep->flushRequired()) {
                $ep->flush($simulate);
            }

            $ep->setup($simulate);
        }

        foreach ($datatype->getObjects($filter) as $id => $object) {
            $this->logger->debug('process write for object ['.(string) $id.'] from data type ['.$datatype->getIdentifier().']', [
                'category' => get_class($this),
            ]);

            foreach ($endpoints as $ep) {
                $this->logger->info('start write onto destination endpoint ['.$ep->getIdentifier().']', [
                    'category' => get_class($this),
                ]);

                try {
                    foreach ($ep->getWorkflows() as $workflow) {
                        $this->logger->debug('start workflow ['.$workflow->getIdentifier().'] for the current object', [
                            'category' => get_class($this),
                        ]);

                        if ($workflow->export($object, $this->timestamp, $simulate) === true) {
                            $this->logger->debug('workflow ['.$workflow->getIdentifier().'] executed for the current object, skip any further workflows for the current data object', [
                                'category' => get_class($this),
                            ]);

                            continue 2;
                        }
                    }

                    $this->logger->debug('no workflow were executed within endpoint ['.$ep->getIdentifier().'] for the current object', [
                        'category' => get_class($this),
                    ]);
                } catch (\Exception $e) {
                    ++$this->error_count;

                    $this->logger->error('failed write object to destination endpoint ['.$ep->getIdentifier().']', [
                        'category' => get_class($this),
                        'object' => $object->getId(),
                        'exception' => $e,
                    ]);

                    if ($ignore === false) {
                        return false;
                    }
                }
            }
        }

        if (count($endpoints) === 0) {
            $this->logger->warning('no destination endpoint active for datatype ['.$datatype->getIdentifier().'], skip export', [
                'category' => get_class($this),
            ]);

            return true;
        }

        foreach ($endpoints as $n => $ep) {
            $ep->shutdown($simulate);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function import(DataTypeInterface $datatype, array $filter = [], array $endpoints = [], bool $simulate = false, bool $ignore = false): bool
    {
        $this->logger->info('start import from source endpoints into data type ['.$datatype->getIdentifier().']', [
            'category' => get_class($this),
        ]);

        $endpoints = $datatype->getSourceEndpoints($endpoints);

        foreach ($endpoints as $ep) {
            $this->logger->info('start import from source endpoint ['.$ep->getIdentifier().']', [
                'category' => get_class($this),
            ]);

            if ($ep->flushRequired()) {
                $datatype->flush($simulate);
            }

            $ep->setup($simulate);

            foreach ($ep->getAll($filter) as $id => $object) {
                $this->logger->debug('process import for object ['.$id.'] into data type ['.$datatype->getIdentifier().']', [
                    'category' => get_class($this),
                    'attributes' => $object,
                ]);

                try {
                    foreach ($ep->getWorkflows() as $workflow) {
                        $this->logger->debug('start workflow ['.$workflow->getIdentifier().'] for the current object', [
                            'category' => get_class($this),
                        ]);

                        if ($workflow->import($datatype, $object, $this->timestamp, $simulate) === true) {
                            $this->logger->debug('workflow ['.$workflow->getIdentifier().'] executed for the current object, skip any further workflows for the current data object', [
                                'category' => get_class($this),
                            ]);

                            continue 2;
                        }
                    }

                    $this->logger->debug('no workflow were executed within endpoint ['.$ep->getIdentifier().'] for the current object', [
                        'category' => get_class($this),
                    ]);
                } catch (\Exception $e) {
                    ++$this->error_count;

                    $this->logger->error('failed import data object from source endpoint ['.$ep->getIdentifier().']', [
                        'category' => get_class($this),
                        'mandator' => $datatype->getMandator()->getName(),
                        'datatype' => $datatype->getName(),
                        'endpoint' => $ep->getName(),
                        'exception' => $e,
                    ]);

                    if ($ignore === false) {
                        return false;
                    }
                }
            }

            $this->garbageCollector($datatype, $ep, $simulate, $ignore);
            $ep->shutdown($simulate);
        }

        if ($endpoints->getReturn() === 0) {
            $this->logger->warning('no source endpoint active for datatype ['.$datatype->getIdentifier().'], skip import', [
                'category' => get_class($this),
            ]);

            return true;
        }

        return true;
    }

    /**
     * Garbage.
     */
    protected function garbageCollector(DataTypeInterface $datatype, EndpointInterface $endpoint, bool $simulate = false, bool $ignore = false): bool
    {
        $this->logger->info('start garbage collector workflows from data type ['.$datatype->getIdentifier().']', [
            'category' => get_class($this),
        ]);

        $filter = [
                '$or' => [
                    [
                        'endpoints.'.$endpoint->getName().'.last_sync' => [
                            '$lte' => $this->timestamp,
                        ],
                    ],
                    [
                        'endpoints' => [
                            '$exists' => 0,
                        ],
                    ],
                ],
        ];

        foreach ($datatype->getObjects($filter, false) as $id => $object) {
            $this->logger->debug('process garbage workflows for garbage object ['.$id.'] from data type ['.$datatype->getIdentifier().']', [
                'category' => get_class($this),
            ]);

            try {
                foreach ($endpoint->getWorkflows() as $workflow) {
                    $this->logger->debug('start workflow ['.$workflow->getIdentifier().'] for the current garbage object', [
                        'category' => get_class($this),
                    ]);

                    if ($workflow->cleanup($object, $this->timestamp, $simulate) === true) {
                        $this->logger->debug('workflow ['.$workflow->getIdentifier().'] executed for the current garbage object, skip any further workflows for the current garbage object', [
                            'category' => get_class($this),
                        ]);

                        break;
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error('failed execute garbage collector for object ['.$id.'] from datatype ['.$datatype->getIdentifier().']', [
                    'category' => get_class($this),
                    'exception' => $e,
               ]);

                if ($ignore === false) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Notify.
     */
    protected function notify(): bool
    {
        if ($this->data['notification']['enabled'] === false) {
            $this->logger->debug('skip notifiaction for job ['.$this->data['job'].'], notification is disabled', [
                'category' => get_class($this),
            ]);

            return false;
        }

        if (count($this->data['notification']['receiver']) === 0) {
            $this->logger->debug('skip notifiaction for job ['.$this->data['job'].'], no receiver configured', [
                'category' => get_class($this),
            ]);
        }

        $iso = $this->timestamp->toDateTime()->format('c');

        if ($this->error_count === 0) {
            $subject = "Job ended with $this->error_count errors";
            $body = "Hi there\n\nThe sync job ".(string) $this->data['job']." started at $iso ended with $this->error_count errors.";
        } else {
            $subject = 'Good job! The job finished with no errors';
            $body = "Hi there\n\nThe sync job ".(string) $this->data['job']." started at $iso finished with no errors.";
        }

        $mail = (new Message())
          ->setSubject($subject)
          ->setBody($body)
          ->setEncoding('UTF-8');

        foreach ($this->data['notification']['receiver'] as $receiver) {
            $mail->setTo($receiver);

            $this->logger->debug('send job notification ['.$this->data['job'].'] to ['.$receiver.']', [
                'category' => get_class($this),
            ]);

            $this->scheduler->addJob(Mail::class, $mail->toString(), [
                Scheduler::OPTION_RETRY => 1,
            ]);
        }

        return true;
    }
}
