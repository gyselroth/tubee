<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Async;

use MongoDB\BSON\UTCDateTime;
use MongoDB\Database;
use Monolog\Handler\MongoDBHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use TaskScheduler\AbstractJob;
use TaskScheduler\Scheduler;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\ResourceNamespace\Factory as ResourceNamespaceFactory;
use Tubee\ResourceNamespace\ResourceNamespaceInterface;
use Zend\Mail\Message;

class Sync extends AbstractJob
{
    /**
     * Log levels.
     */
    public const LOG_LEVELS = [
        'debug' => Logger::DEBUG,
        'info' => Logger::INFO,
        'notice' => Logger::NOTICE,
        'warning' => Logger::WARNING,
        'error' => Logger::ERROR,
        'critical' => Logger::CRITICAL,
        'alert' => Logger::ALERT,
        'emergency' => Logger::EMERGENCY,
    ];

    /**
     * ResourceNamespace factory.
     *
     * @var ResourceNamespaceFactory
     */
    protected $namespace_factory;

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
     * Database.
     *
     * @var Database
     */
    protected $db;

    /**
     * Process stack.
     *
     * @var array
     */
    protected $stack = [];

    /**
     * Resource namespace.
     *
     * @var ResourceNamespaceInterface
     */
    protected $namespace;

    /**
     * Sync.
     */
    public function __construct(ResourceNamespaceFactory $namespace_factory, Database $db, Scheduler $scheduler, LoggerInterface $logger)
    {
        $this->namespace_factory = $namespace_factory;
        $this->scheduler = $scheduler;
        $this->logger = $logger;
        $this->db = $db;
        $this->timestamp = new UTCDateTime();
    }

    /**
     * Start job.
     */
    public function start(): bool
    {
        $this->namespace = $this->namespace_factory->getOne($this->data['namespace']);

        foreach ($this->data['collections'] as $collections) {
            $collections = (array) $collections;
            $filter = in_array('*', $collections) ? [] : ['name' => ['$in' => $collections]];
            $collections = iterator_to_array($this->namespace->getCollections($filter));

            $endpoints = $this->data['endpoints'];
            $this->loopCollections($collections, $endpoints);
        }

        $this->notify();

        return true;
    }

    /**
     * Loop collections.
     */
    protected function loopCollections(array $collections, array $endpoints)
    {
        foreach ($endpoints as $ep) {
            foreach ($collections as $collection) {
                $this->loopEndpoints($collection, $collections, (array) $ep, $endpoints);
            }

            $this->logger->debug('wait for child stack ['.count($this->stack).'] to be finished', [
                'category' => get_class($this),
            ]);

            foreach ($this->stack as $proc) {
                $proc->wait();
            }
        }
    }

    /**
     * Loop endpoints.
     */
    protected function loopEndpoints(CollectionInterface $collection, array $all_collections, array $endpoints, array $all_endpoints)
    {
        $filter = in_array('*', $endpoints) ? [] : ['name' => ['$in' => $endpoints]];
        $endpoints = iterator_to_array($collection->getEndpoints($filter));

        foreach ($endpoints as $endpoint) {
            if (count($all_endpoints) > 1 || count($all_collections) > 1) {
                $data = $this->data;
                $data = array_merge($data, [
                    'collections' => [$collection->getName()],
                    'endpoints' => [$endpoint->getName()],
                    'parent' => $this->getId(),
                ]);

                $this->stack[] = $this->scheduler->addJob(self::class, $data);
            } else {
                $this->execute($collection, $endpoint);
            }
        }
    }

    /**
     * Execute.
     */
    protected function execute(CollectionInterface $collection, EndpointInterface $endpoint)
    {
        $this->setupLogger(self::LOG_LEVELS[$this->data['log_level']], [
            'process' => (string) $this->getId(),
            'parent' => isset($this->data['parent']) ? (string) $this->data['parent'] : null,
            'start' => $this->timestamp,
            'namespace' => $this->namespace->getName(),
            'collection' => $collection->getName(),
            'endpoint' => $endpoint->getName(),
        ]);

        if ($endpoint->getType() === EndpointInterface::TYPE_SOURCE) {
            $this->import($collection, $this->getFilter(), ['name' => $endpoint->getName()], $this->data['simulate'], $this->data['ignore']);
        } elseif ($endpoint->getType() === EndpointInterface::TYPE_DESTINATION) {
            $this->export($collection, $this->getFilter(), ['name' => $endpoint->getName()], $this->data['simulate'], $this->data['ignore']);
        } else {
            $this->logger->warning('skip endpoint ['.$endpoint->getIdentifier().'], endpoint type is neither source nor destination', [
                'category' => get_class($this),
            ]);
        }

        $this->logger->popProcessor();
    }

    /**
     * Decode filter.
     */
    protected function getFilter(): array
    {
        if ($this->data['filter'] === null) {
            return [];
        }

        return (array) json_decode($this->data['filter']);
    }

    /**
     * Set logger level.
     */
    protected function setupLogger(int $level, array $context): bool
    {
        if (isset($this->data['job'])) {
            $context['job'] = (string) $this->data['job'];
        }

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
    protected function export(CollectionInterface $collection, array $filter = [], array $endpoints = [], bool $simulate = false, bool $ignore = false): bool
    {
        $this->logger->info('start export to destination endpoints from data type ['.$collection->getIdentifier().']', [
            'category' => get_class($this),
        ]);

        $endpoints = iterator_to_array($collection->getDestinationEndpoints($endpoints));
        $workflows = [];

        foreach ($endpoints as $ep) {
            if ($ep->flushRequired()) {
                $ep->flush($simulate);
            }

            $ep->setup($simulate);
        }

        $i = 0;
        foreach ($collection->getObjects($filter) as $id => $object) {
            ++$i;
            $this->logger->debug('process ['.$i.'] export for object ['.(string) $id.'] - [{fields}] from data type ['.$collection->getIdentifier().']', [
                'category' => get_class($this),
                'fields' => array_keys($object->toArray()),
            ]);

            foreach ($endpoints as $ep) {
                $identifier = $ep->getIdentifier();
                $this->logger->info('start export to destination endpoint ['.$identifier.']', [
                    'category' => get_class($this),
                ]);

                if (!isset($workflows[$identifier])) {
                    $workflows[$identifier] = iterator_to_array($ep->getWorkflows(['kind' => 'Workflow']));
                }

                try {
                    foreach ($workflows[$identifier] as $workflow) {
                        $this->logger->debug('start workflow ['.$workflow->getIdentifier().'] [ensure='.$workflow->getEnsure().'] for the current object', [
                            'category' => get_class($this),
                        ]);

                        if ($workflow->export($object, $this->timestamp, $simulate) === true) {
                            $this->logger->debug('workflow ['.$workflow->getIdentifier().'] executed for the object ['.(string) $id.'], skip any further workflows for the current data object', [
                                'category' => get_class($this),
                            ]);

                            continue 2;
                        }
                        $this->logger->debug('skip workflow ['.$workflow->getIdentifier().'] for object ['.(string) $id.'], condition does not match or unusable ensure', [
                                'category' => get_class($this),
                            ]);
                    }

                    $this->logger->debug('no workflow were executed within endpoint ['.$identifier.'] for the current object', [
                        'category' => get_class($this),
                    ]);
                } catch (\Exception $e) {
                    ++$this->error_count;

                    $this->logger->error('failed export object to destination endpoint ['.$identifier.']', [
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
            $this->logger->warning('no destination endpoint available for collection ['.$collection->getIdentifier().'], skip export', [
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
    protected function import(CollectionInterface $collection, array $filter = [], array $endpoints = [], bool $simulate = false, bool $ignore = false): bool
    {
        $this->logger->info('start import from source endpoints into data type ['.$collection->getIdentifier().']', [
            'category' => get_class($this),
        ]);

        $endpoints = $collection->getSourceEndpoints($endpoints);
        $workflows = [];

        foreach ($endpoints as $ep) {
            $identifier = $ep->getIdentifier();
            $this->logger->info('start import from source endpoint ['.$identifier.']', [
                'category' => get_class($this),
            ]);

            if ($ep->flushRequired()) {
                $collection->flush($simulate);
            }

            $ep->setup($simulate);
            if (!isset($workflows[$identifier])) {
                $workflows[$identifier] = iterator_to_array($ep->getWorkflows(['kind' => 'Workflow']));
            }

            $i = 0;
            foreach ($ep->getAll($filter) as $id => $object) {
                ++$i;
                $this->logger->debug('process ['.$i.'] import for object ['.$id.'] into data type ['.$collection->getIdentifier().']', [
                    'category' => get_class($this),
                    'attributes' => $object,
                ]);

                try {
                    foreach ($workflows[$identifier] as $workflow) {
                        $this->logger->debug('start workflow ['.$workflow->getIdentifier().'] [ensure='.$workflow->getEnsure().'] for the current object', [
                            'category' => get_class($this),
                        ]);

                        if ($workflow->import($collection, $object, $this->timestamp, $simulate) === true) {
                            $this->logger->debug('workflow ['.$workflow->getIdentifier().'] executed for the object ['.(string) $id.'], skip any further workflows for the current data object', [
                                'category' => get_class($this),
                            ]);

                            continue 2;
                        }
                        $this->logger->debug('skip workflow ['.$workflow->getIdentifier().'] for object ['.(string) $id.'], condition does not match or unusable ensure', [
                                'category' => get_class($this),
                            ]);
                    }

                    $this->logger->debug('no workflow were executed within endpoint ['.$identifier.'] for the current object', [
                        'category' => get_class($this),
                    ]);
                } catch (\Exception $e) {
                    ++$this->error_count;

                    $this->logger->error('failed import data object from source endpoint ['.$identifier.']', [
                        'category' => get_class($this),
                        'namespace' => $collection->getResourceNamespace()->getName(),
                        'collection' => $collection->getName(),
                        'endpoint' => $ep->getName(),
                        'exception' => $e,
                    ]);

                    if ($ignore === false) {
                        return false;
                    }
                }
            }

            if (empty($filter)) {
                $this->garbageCollector($collection, $ep, $simulate, $ignore);
            } else {
                $this->logger->info('skip garbage collection, a query has been issued for import', [
                    'category' => get_class($this),
                ]);
            }

            $ep->shutdown($simulate);
        }

        if ($endpoints->getReturn() === 0) {
            $this->logger->warning('no source endpoint available for collection ['.$collection->getIdentifier().'], skip import', [
                'category' => get_class($this),
            ]);

            return true;
        }

        return true;
    }

    /**
     * Garbage.
     */
    protected function garbageCollector(CollectionInterface $collection, EndpointInterface $endpoint, bool $simulate = false, bool $ignore = false): bool
    {
        $this->logger->info('start garbage collector workflows from data type ['.$collection->getIdentifier().']', [
            'category' => get_class($this),
        ]);

        $filter = [
            'endpoints.'.$endpoint->getName().'.last_sync' => [
                '$lt' => $this->timestamp,
            ],
        ];

        $this->db->{$collection->getCollection()}->updateMany($filter, ['$set' => [
            'endpoints.'.$endpoint->getName().'.garbage' => true,
        ]]);

        $workflows = iterator_to_array($endpoint->getWorkflows(['kind' => 'GarbageWorkflow']));

        $i = 0;
        foreach ($collection->getObjects($filter, false) as $id => $object) {
            ++$i;
            $this->logger->debug('process ['.$i.'] garbage workflows for garbage object ['.$id.'] from data type ['.$collection->getIdentifier().']', [
                'category' => get_class($this),
            ]);

            try {
                foreach ($workflows as $workflow) {
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
                $this->logger->error('failed execute garbage collector for object ['.$id.'] from collection ['.$collection->getIdentifier().']', [
                    'category' => get_class($this),
                    'exception' => $e,
                ]);

                if ($ignore === false) {
                    return false;
                }
            }
        }

        $this->relationGarbageCollector($collection, $endpoint, $workflows);

        return true;
    }

    /**
     * Relation garbage collector.
     */
    protected function relationGarbageCollector(CollectionInterface $collection, EndpointInterface $endpoint, $workflows)
    {
        $namespace = $endpoint->getCollection()->getResourceNamespace()->getName();
        $collection = $endpoint->getCollection()->getName();
        $ep = $endpoint->getName();
        $key = join('/', [$namespace, $collection, $ep]);

        $filter = [
            'endpoints.'.$key.'.last_sync' => [
                '$lt' => $this->timestamp,
            ],
        ];

        $this->db->relations->updateMany($filter, ['$set' => [
            'endpoints.'.$key.'.garbage' => true,
        ]]);

        foreach ($workflows as $workflow) {
            foreach ($workflow->getAttributeMap()->getMap() as $attr) {
                if (isset($attr['map']) && $attr['map']['ensure'] === 'absent') {
                    $this->db->relations->deleteMany(['endpoints.'.$key.'.garbage' => true]);
                }
            }
        }
    }

    /**
     * Notify.
     */
    protected function notify(): bool
    {
        if ($this->data['notification']['enabled'] === false) {
            $this->logger->debug('skip notifiaction for process ['.$this->getId().'], notification is disabled', [
                'category' => get_class($this),
            ]);

            return false;
        }

        if (count($this->data['notification']['receiver']) === 0) {
            $this->logger->debug('skip notifiaction for process ['.$this->getId().'], no receiver configured', [
                'category' => get_class($this),
            ]);
        }

        $iso = $this->timestamp->toDateTime()->format('c');

        if ($this->error_count === 0) {
            $subject = "Job ended with $this->error_count errors";
            $body = "Hi there\n\nThe sync process ".(string) $this->getId()." started at $iso ended with $this->error_count errors.";
        } else {
            $subject = 'Good job! The job finished with no errors';
            $body = "Hi there\n\nThe sync process ".(string) $this->getId()." started at $iso finished with no errors.";
        }

        $mail = (new Message())
          ->setSubject($subject)
          ->setBody($body)
          ->setEncoding('UTF-8');

        foreach ($this->data['notification']['receiver'] as $receiver) {
            $mail->setTo($receiver);

            $this->logger->debug('send process notification ['.$this->getId().'] to ['.$receiver.']', [
                'category' => get_class($this),
            ]);

            $this->scheduler->addJob(Mail::class, $mail->toString(), [
                Scheduler::OPTION_RETRY => 1,
            ]);
        }

        return true;
    }
}
