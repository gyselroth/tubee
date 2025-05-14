<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Async;

//use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Database;
use Monolog\Handler\MongoDBHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use TaskScheduler\AbstractJob;
use TaskScheduler\JobInterface;
use TaskScheduler\Scheduler;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Helper;
use Tubee\Log\MongoDBFormatter;
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

//    /**
//     * Failed objects.
//     *
//     * @var array
//     */
//    protected $failed_objects = [];

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
    }

    /**
     * Start job.
     */
    public function start(): bool
    {
        $this->timestamp = new UTCDateTime();
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
     * Get timestamp.
     */
    public function getTimestamp(): ?UTCDateTime
    {
        return $this->timestamp;
    }

//    public function notification(int $status, array $job): bool
//    {
//        if ($status >= 3 && $this->checkReceiver($job['data'], $job['_id'])) {
//            $namespace = $job['data']['namespace'];
//            $collections = implode(', ', $this->getArrayDataByKey($job['data'], 'collections'));
//            $endpoints = implode(', ', $this->getArrayDataByKey($job['data'], 'endpoints'));
//
//            switch ($status) {
//                case JobInterface::STATUS_DONE:
//                    $subject = '['.$namespace.']: sync process ended successfully';
//                    $body = "Hi there\n\nThe sync process on collections [$collections] and endpoints [$endpoints] ended successfully.";
//
//                    if ($job['data']['error_count'] === 0) {
//                        $body = $body."\n\nThe process finished with no errors.";
//                    } else {
//                        $error_count = $job['data']['error_count'];
//                        $body = $body."\n\nThe process ended with $error_count errors: \n - ".implode(", \n - ", $job['data']['failed_objects']);
//                    }
//
//                    break;
//                case JobInterface::STATUS_FAILED:
//                    $subject = '['.$namespace.']: sync process failed';
//                    $body = "Hi there\n\nThe sync process on collections [$collections] and endpoints [$endpoints] failed. Check tubee log for further information";
//
//                    break;
//                case JobInterface::STATUS_CANCELED:
//                    $subject = '['.$namespace.']: sync process canceled';
//                    $body = "Hi there\n\nThe sync process on collections [$collections] and endpoints [$endpoints] was canceled. Check tubee log for further information";
//
//                    break;
//                case JobInterface::STATUS_TIMEOUT:
//                    $subject = '['.$namespace.']: sync process ran into timeout';
//                    $body = "Hi there\n\nThe sync process on collections [$collections] and endpoints [$endpoints] ran into a timeout.";
//
//                    break;
//                default:
//                    return false;
//            }
//
//            $body = $body."\n\n\n".'Process ID: ['.$job['_id'].']';
//            $body = $body."\nDate: [".date('d.m.Y H:i:s', time()).']';
//
//            $mail = (new Message())
//                ->setSubject($subject)
//                ->setBody($body)
//                ->setEncoding('UTF-8');
//
//            foreach ($job['data']['notification']['receiver'] as $receiver) {
//                $mail->setTo($receiver);
//                $this->logger->debug('send process notification ['.$job['_id'].'] to ['.$receiver.']', [
//                    'category' => get_class($this),
//                ]);
//
//                $this->scheduler->addJob(Mail::class, $mail->toString(), [
//                    Scheduler::OPTION_RETRY => 1,
//                    Scheduler::OPTION_FORCE_SPAWN => true,
//                ]);
//            }
//
//            return true;
//        }
//
//        return false;
//    }

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

            $i = 0;
            foreach ($this->stack as $proc) {
                ++$i;
                if (in_array($this->scheduler->getJob($proc->getId())->getStatus(), JobInterface::PENDING_JOBS)) {
                    $proc->wait();
                }

                $this->updateProgress($i / count($this->stack) * 100);

                $record = $this->db->{$this->scheduler->getJobQueue()}->findOne([
                    '_id' => $proc->getId(),
                ]);

                $this->error_count += $record['data']['error_count'] ?? 0;
//                $this->failed_objects += $record['data']['failed_objects'] ?? [];
                $this->increaseErrorCount();
            }

            $this->stack = [];
        }
    }

    /**
     * Update error count.
     */
    protected function increaseErrorCount(): self
    {
        $this->db->{$this->scheduler->getJobQueue()}->updateOne([
            '_id' => $this->getId(),
            'data.error_count' => ['$exists' => true],
        ], [
            '$set' => ['data.error_count' => $this->error_count],
        ]);

//        $this->db->{$this->scheduler->getJobQueue()}->updateOne([
//            '_id' => $this->getId(),
//            'data.error_count' => ['$exists' => true],
//        ], [
//            '$set' => [
//                'data.error_count' => $this->error_count,
//                'data.failed_objects' => $this->failed_objects,
//            ],
//        ]);

        return $this;
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
                $parentJob = $this->scheduler->getJob($this->getId())->toArray();

                if (isset($parentJob['status']) && in_array($parentJob['status'], JobInterface::FAILED_JOBS)) {
                    $this->logger->debug('parent job ['.$parentJob['_id'].'] not running anymore. do not add new child job', [
                        'category' => get_class($this),
                    ]);

                    continue;
                }

                $data = $this->data;
                $data = array_merge($data, [
                    'collections' => [$collection->getName()],
                    'endpoints' => [$endpoint->getName()],
                    'parent' => $this->getId(),
                ]);

                $data['notification'] = ['enabled' => false, 'receiver' => []];
                $this->stack[] = $this->scheduler->addJob(self::class, $data);
            } else {
                $this->execute($collection, $endpoint);
                $this->increaseErrorCount();
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

        return (array) Helper::jsonDecode(stripslashes($this->data['filter']));
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
                $handler->setFormatter(new MongoDBFormatter());
            }
        }

        while (count($this->logger->getProcessors()) > 1) {
            $this->logger->popProcessor();
        }

        $this->logger->pushProcessor(function ($record) use ($context) {
            $record['context'] = array_merge($record['context'], $context);

            return $record;
        });

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

        $total = $collection->countObjects($filter);
        $i = 0;

        $this->logger->debug('export a total amount of ['.$total.'] objects to endpoint', [
            'category' => get_class($this),
        ]);

        foreach ($collection->getObjects($filter) as $id => $object) {
            $this->updateProgress(($total === 0) ? 0 : $i / $total * 100);
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

                    if (count($workflows[$identifier]) === 0) {
                        $this->logger->warning('no workflows available in destination endpoint ['.$ep->getIdentifier().'], skip export', [
                            'category' => get_class($this),
                        ]);

                        continue;
                    }
                }

                try {
                    foreach ($workflows[$identifier] as $workflow) {
                        $this->logger->debug('start workflow ['.$workflow->getIdentifier().'] [ensure='.$workflow->getEnsure().'] for the current object', [
                            'category' => get_class($this),
                        ]);

                        if ($workflow->export($object, $this, $simulate) === true) {
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
                } catch (\Throwable $e) {
                    ++$this->error_count;
//                    $failed_object = $object->getData();

//                    if (isset($this->data['notification']['identifier'])) {
//                        foreach ($this->data['notification']['identifier'] as $object_identifier) {
//                            if (isset($failed_object[$object_identifier])) {
//                                $this->failed_objects[] = $failed_object[$object_identifier];
//
//                                break;
//                            }
//                        }
//                    }

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

                if (count($workflows[$identifier]) === 0) {
                    $this->logger->warning('no workflows available in source endpoint ['.$ep->getIdentifier().'], skip import', [
                        'category' => get_class($this),
                    ]);

                    continue;
                }
            }

            $i = 0;
            $total = $ep->count($filter);

            foreach ($ep->getAll($filter) as $id => $object) {
                $this->updateProgress(($total === 0) ? 0 : $i / $total * 100);
                ++$i;
                $this->logger->debug('process object ['.$i.'] import for object ['.$object->getId().'] into data type ['.$collection->getIdentifier().']', [
                    'category' => get_class($this),
                    'attributes' => $object,
                ]);

                try {
                    foreach ($workflows[$identifier] as $workflow) {
                        $this->logger->debug('start workflow ['.$workflow->getIdentifier().'] [ensure='.$workflow->getEnsure().'] for the current object', [
                            'category' => get_class($this),
                        ]);

                        if ($workflow->import($collection, $object, $this, $simulate) === true) {
                            $this->logger->debug('workflow ['.$workflow->getIdentifier().'] executed for the object ['.(string) $object->getId().'], skip any further workflows for the current data object', [
                                'category' => get_class($this),
                            ]);

                            continue 2;
                        }
                        $this->logger->debug('skip workflow ['.$workflow->getIdentifier().'] for object ['.(string) $object->getId().'], condition does not match or unusable ensure', [
                            'category' => get_class($this),
                        ]);
                    }

                    $this->logger->debug('no workflow were executed within endpoint ['.$identifier.'] for the current object', [
                        'category' => get_class($this),
                    ]);
                } catch (\Throwable $e) {
                    ++$this->error_count;
//                    $failed_object = $object->getData();
//
//                    if (isset($this->data['notification']['identifier'])) {
//                        foreach ($this->data['notification']['identifier'] as $object_identifier) {
//                            if (isset($failed_object[$object_identifier])) {
//                                $this->failed_objects[] = $failed_object[$object_identifier];
//
//                                break;
//                            }
//                        }
//                    }

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
        $this->logger->info('start garbage collector workflows from data type [{identifier}] for data objects older than [{timestamp}] (last_sync)', [
            'timestamp' => $this->timestamp,
            'identifier' => $collection->getIdentifier(),
            'category' => get_class($this),
        ]);

        $workflows = iterator_to_array($endpoint->getWorkflows(['kind' => 'GarbageWorkflow']));
        if (count($workflows) === 0) {
            $this->logger->info('no garbage workflows available in ['.$endpoint->getIdentifier().'], skip garbage collection', [
                'category' => get_class($this),
            ]);

            return false;
        }

        $relationObject = false;
        foreach ($workflows as $workflow) {
            foreach ($workflow->getAttributeMap()->getMap() as $attr) {
                if ($attr['name'] === 'relationObject' || (isset($attr['map']) && $attr['map']['ensure'] === 'absent')) {
                    $relationObject = true;
                }
            }
        }

        if ($relationObject) {
            $this->logger->info('run garbage workflows for relations', [
                'category' => get_class($this),
            ]);

            return $this->relationGarbageCollector($collection, $endpoint, $workflows, $simulate);
        }

        $this->logger->info('run garbage workflows for DataObjects', [
            'category' => get_class($this),
        ]);

        return $this->objectGarbageCollector($endpoint, $collection, $workflows, $simulate, $ignore);
    }

    /**
     * Object garbage collector.
     */
    protected function objectGarbageCollector(EndpointInterface $endpoint, CollectionInterface $collection, $workflows, $simulate, $ignore): bool
    {
        $filter = [
            'endpoints.'.$endpoint->getName().'.last_sync' => [
                '$lt' => $this->timestamp,
            ],
        ];

        $this->db->{$collection->getCollection()}->updateMany($filter, ['$set' => [
            'endpoints.'.$endpoint->getName().'.garbage' => true,
        ]]);

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

                    if ($workflow->cleanup($object, $this, $simulate) === true) {
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

        return true;
    }

    /**
     * Relation garbage collector.
     */
    protected function relationGarbageCollector(CollectionInterface $collection, EndpointInterface $endpoint, $workflows, $simulate): bool
    {
        $namespace = $endpoint->getCollection()->getResourceNamespace();
        $collection = $endpoint->getCollection();
        $key = join('/', [$namespace->getName(), $collection->getName(), $endpoint->getName()]);

        $this->logger->info('mark all relation data objects older than [{timestamp}] (last_sync) as garbage', [
            'class' => get_class($this),
            'timestamp' => $this->timestamp,
        ]);

        $filter = [
            'endpoints.'.$key.'.last_sync' => [
                '$lt' => $this->timestamp,
            ],
        ];

        $this->db->relations->updateMany($filter, ['$set' => [
            'endpoints.'.$key.'.garbage' => true,
        ]]);

        $garbageRelations = $this->db->relations->find(['endpoints.'.$key.'.garbage' => true])->toArray();

        $i = 0;
        foreach ($garbageRelations as $relation) {
            ++$i;
            $this->logger->debug('process ['.$i.'] garbage workflows for garbage relation ['.$relation['name'].'] from data type ['.$collection->getIdentifier().']', [
                'category' => get_class($this),
            ]);

            try {
                foreach ($workflows as $workflow) {
                    $this->logger->debug('start workflow ['.$workflow->getIdentifier().'] for the current garbage relation', [
                        'category' => get_class($this),
                    ]);

                    if ($workflow->relationCleanup($this->db->{'relations'}, $relation, $this, $namespace, $endpoint, $workflow, $simulate) === true) {
                        $this->logger->debug('workflow ['.$workflow->getIdentifier().'] executed for the current garbage object, skip any further workflows for the current garbage object', [
                            'category' => get_class($this),
                        ]);

                        break;
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error('failed execute garbage collector for relation ['.$relation['name'].'] from collection ['.$collection->getIdentifier().']', [
                    'category' => get_class($this),
                    'exception' => $e,
                ]);
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
            $subject = 'Good job! The job finished with no errors';
            $body = "Hi there\n\nThe sync process ".(string) $this->getId()." started at $iso finished with no errors.";
        } else {
            $subject = "Job ended with $this->error_count errors";
            $body = "Hi there\n\nThe sync process ".(string) $this->getId()." started at $iso ended with $this->error_count errors.";
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

//    protected function checkReceiver(array $job_data, ObjectId $job_id): bool
//    {
//        if ($job_data['notification']['enabled'] === false) {
//            $this->logger->debug('skip notification for process ['.$job_id.'], notification is disabled', [
//                'category' => get_class($this),
//            ]);
//
//            return false;
//        }
//
//        if (count($job_data['notification']['receiver']) === 0) {
//            $this->logger->debug('skip notification for process ['.$job_id.'], no receiver configured', [
//                'category' => get_class($this),
//            ]);
//
//            return false;
//        }
//
//        return true;
//    }
//
//    protected function getArrayDataByKey(array $jobData, string $key): array
//    {
//        $return = [];
//
//        foreach ($jobData as $index => $value) {
//            if ($index === $key) {
//                foreach ($value as $data) {
//                    if (is_array($data)) {
//                        $return = array_merge($return, $this->getArrayDataByKey([$key => $data], $key));
//                    } else {
//                        $return[] = $data;
//                    }
//                }
//            }
//        }
//
//        return $return;
//    }
}
