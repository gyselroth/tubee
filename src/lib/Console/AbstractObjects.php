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
use InvalidArgumentException;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Yaml;
use TaskScheduler\Scheduler;
use Tubee\Async\Sync as SyncJob;
use Tubee\DataType\DataTypeInterface;
use Tubee\Manager;

abstract class AbstractObjects
{
    /**
     * Sync actions.
     */
    const SYNC_BIDIRECTIONAL = 0;
    const SYNC_IMPORT = 1;
    const SYNC_EXPORT = 2;

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
     * Constructor.
     *
     * @param GetOpt          $getopt
     * @param Manager         $manager
     * @param LoggerInterface $logger
     * @param Scheduler       $scheduler
     */
    public function __construct(GetOpt $getopt, Manager $manager, LoggerInterface $logger, Scheduler $scheduler)
    {
        $this->logger = $logger;
        $this->getopt = $getopt;
        $this->manager = $manager;
        $this->scheduler = $scheduler;
    }

    /**
     * Get version.
     */
    protected function getVersion(): int
    {
        return (int) $this->getopt->getOption('version');
    }

    /**
     * Get diff tool.
     */
    protected function getDiffTool(): string
    {
        $editor = getenv('DIFFTOOL');
        if ($editor === false) {
            throw new InvalidArgumentException('no diff tool available on your system, set env DIFFTOOL');
        }

        return $editor;
    }

    /**
     * Get editor.
     */
    protected function getEditor(): string
    {
        $editor = getenv('EDITOR');
        if ($editor === false) {
            throw new InvalidArgumentException('no editor available on your system, set env EDITOR');
        }

        return $editor;
    }

    /**
     * Prepare task options.
     */
    protected function prepareTask(array $options): array
    {
        $result = [];
        foreach ($options as $key => $option) {
            if ($option !== null) {
                $result[$key] = (int) $option;
            }
        }

        return $result;
    }

    /**
     * Schedule task.
     */
    protected function scheduleTask(int $action, array $mandators = [], array $datatypes = [], array $filter = []): Objects
    {
        $job = [
            'action' => $action,
            'mandator' => $mandators,
            'datatypes' => $datatypes,
            'filter' => $filter,
            'endpoints' => $this->getEndpoints(),
            'simulate' => $this->isSimulate(),
            'ignore' => $this->isIgnore(),
        ];

        $task = [
            Scheduler::OPTION_AT => $this->getopt->getOption('task-at'),
            Scheduler::OPTION_INTERVAL => $this->getopt->getOption('task-interval'),
            Scheduler::OPTION_RETRY_INTERVAL => $this->getopt->getOption('task-retry-interval'),
            Scheduler::OPTION_RETRY => $this->getopt->getOption('task-retry'),
        ];

        $id = $this->scheduler->addJob(SyncJob::class, $job, $this->prepareTask($task));
        $this->logger->info('scheduled new sync job ['.$id.']', [
            'category' => get_class($this),
        ]);

        return $this;
    }

    /**
     * Simulate mode requested?
     */
    protected function isSimulate(): bool
    {
        return (bool) $this->getopt->getOption('simulate');
    }

    /**
     * Ignore mode requested?
     */
    protected function isIgnore(): bool
    {
        return (bool) $this->getopt->getOption('ignore');
    }

    /**
     * Get endpoints.
     */
    protected function getEndpoints(): array
    {
        if ($endpoints = $this->getopt->getOption('endpoints')) {
            return explode(',', $this->getopt->getOption('endpoints'));
        }

        return [];
    }

    /**
     * Export objects.
     */
    protected function exportObject(DataTypeInterface $datatype, array $filter): Objects
    {
        if ($this->getopt->getOption('export') === null) {
            return $this;
        }

        if ($this->getopt->getOption('async')) {
            return $this->scheduleTask(
                self::SYNC_EXPORT[$datatype->getMandator->getName()],
                [$datatype->getName()],
                $filter
            );
        }

        $datatype->export(
            new UTCDateTime(),
            $filter,
            $this->getEndpoints(),
            $this->isSimulate(),
            $this->isIgnore()
        );

        return $this;
    }

    /**
     * Read stream.
     *
     * @param mixed $stream
     */
    protected function readStream($stream): array
    {
        $contents = '';
        rewind($stream);
        while ($chunk = fread($stream, 1024)) {
            $contents .= $chunk;
        }

        return Yaml::parse($contents);
    }

    /**
     * Get meta attributes if requested via --meta.
     */
    protected function getAttributes(): array
    {
        $attributes = ['id', 'mandator', 'datatype', 'data'];

        if ($this->getopt->getOption('meta')) {
            $attributes = [];
        }

        return $attributes;
    }

    /**
     * Get mandators list.
     */
    protected function getMandatorsList(): array
    {
        if ($mandators = $this->getopt->getOperand('mandator')) {
            return explode(',', $this->getopt->getOperand('mandator'));
        }

        return [];
    }

    /**
     * Get datatypes list.
     */
    protected function getDataTypesList(): array
    {
        if ($mandators = $this->getopt->getOperand('datatype')) {
            return explode(',', $this->getopt->getOperand('datatype'));
        }

        return [];
    }

    /**
     * Get filter.
     */
    protected function getFilter(): array
    {
        if ($id = $this->getopt->getOperand('id')) {
            return ['_id' => new ObjectId($id)];
        }

        $result = [];
        $filter = $this->getopt->getOption('filter');

        if ($filter === null) {
            return [];
        }

        $parts = explode(',', $filter);
        foreach ($parts as $filter) {
            $keys = explode('=', $filter);
            $result[][$keys[0]] = $keys[1];
        }

        return ['$or' => $result];
    }

    /**
     * Validate input.
     *
     * @param mixed $object
     */
    protected function validateInput($object): array
    {
        $object = (array) $object;

        if (!isset($object['mandator'])) {
            throw new InvalidArgumentException('mandator is required');
        }

        $object['mandator'] = (string) $object['mandator'];

        if (!isset($object['datatype'])) {
            throw new InvalidArgumentException('mandator is required');
        }

        $object['datatype'] = (string) $object['datatype'];

        if (!isset($object['data'])) {
            throw new InvalidArgumentException('data array is required');
        }

        $object['data'] = (array) $object['data'];

        return $object;
    }

    /**
     * Update objects.
     */
    protected function updateObjects(array $objects, array $update): Objects
    {
        $export = [];
        foreach ($update as $key => $object) {
            $object = $this->validateInput($object);

            if (!isset($object['id'])) {
                throw new InvalidArgumentException('object id is required');
            }

            $id = new ObjectId((string) $object['id']);
            $mandator = $this->manager->getMandator($object['mandator']);
            $datatype = $mandator->getDataType($object['datatype']);
            $version = $datatype->change($objects[$object['id']], $object['data'], $this->isSimulate());

            if ($version > $objects[$object['id']]->getVersion()) {
                $export[$mandator->getName()][$datatype->getName()]['datatype'] = $datatype;
                $export[$mandator->getName()][$datatype->getName()]['id'][] = $id;
            }
        }

        foreach ($export as $mandator => $datatypes) {
            foreach ($datatypes as $name => $datatype) {
                $this->exportObject($datatype['datatype'], ['_id' => ['$in' => $datatype['id']]]);
            }
        }

        return $this;
    }

    /**
     * Create new object.
     */
    protected function createObject(array $object): Objects
    {
        $object = $this->validateInput($object);
        $mandator = $this->manager->getMandator($object['mandator']);
        $datatype = $mandator->getDataType($object['datatype']);
        $id = $datatype->create($object['data']);
        $this->exportObject($datatype, ['_id' => $id]);

        return $this;
    }
}
