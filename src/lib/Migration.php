<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use MongoDB\Database;
use Psr\Log\LoggerInterface;
use Tubee\Migration\DeltaInterface;
use Tubee\Migration\Exception;

class Migration
{
    /**
     * Databse.
     *
     * @var Database
     */
    protected $db;

    /**
     * LoggerInterface.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Deltas.
     *
     * @var array
     */
    protected $deltas = [];

    /**
     * Construct.
     */
    public function __construct(Database $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Check if delta was applied.
     */
    public function isDeltaApplied(string $class): bool
    {
        return null !== $this->db->deltas->findOne(['class' => $class]);
    }

    /**
     * Execute migration deltas.
     */
    public function start(bool $force = false, bool $ignore = false, array $deltas = []): bool
    {
        $this->logger->info('execute migration deltas', [
            'category' => get_class($this),
        ]);

        $instances = [];

        if (0 === count($this->deltas)) {
            $this->logger->warning('no deltas have been configured', [
                'category' => get_class($this),
            ]);

            return false;
        }

        foreach ($this->getDeltas($deltas) as $name => $delta) {
            if (false === $force && $this->isDeltaApplied($name)) {
                $this->logger->debug('skip existing delta ['.$name.']', [
                    'category' => get_class($this),
                ]);
            } else {
                $this->logger->info('apply delta ['.$name.']', [
                    'category' => get_class($this),
                ]);

                try {
                    $delta->start();
                    $this->db->deltas->insertOne(['class' => get_class($delta)]);
                } catch (\Exception $e) {
                    $this->logger->error('failed to apply delta ['.get_class($delta).']', [
                        'category' => get_class($this),
                        'exception' => $e,
                    ]);

                    if ($ignore === false) {
                        throw $e;
                    }
                }
            }
        }

        $this->logger->info('executed migration deltas successfully', [
            'category' => get_class($this),
        ]);

        return true;
    }

    /**
     * Has delta.
     */
    public function hasDelta(string $name): bool
    {
        return isset($this->deltas[$name]);
    }

    /**
     * Inject delta.
     */
    public function injectDelta(DeltaInterface $delta, ?string $name = null): self
    {
        if (null === $name) {
            $name = get_class($delta);
        }

        $this->logger->debug('inject delta ['.$name.'] of type ['.get_class($delta).']', [
            'category' => get_class($this),
        ]);

        if ($this->hasDelta($name)) {
            throw new Exception\NotUnique('delta '.$name.' is already registered');
        }

        $this->deltas[$name] = $delta;

        return $this;
    }

    /**
     * Get delta.
     */
    public function getDelta(string $name): DeltaInterface
    {
        if (!$this->hasDelta($name)) {
            throw new Exception\NotFound('delta '.$name.' is not registered');
        }

        return $this->deltas[$name];
    }

    /**
     * Get deltas.
     */
    public function getDeltas(array $deltas = []): array
    {
        if (empty($deltas)) {
            return $this->deltas;
        }
        $list = [];
        foreach ($deltas as $name) {
            if (!$this->hasDelta($name)) {
                throw new Exception\NotFound('delta '.$name.' is not registered');
            }
            $list[$name] = $this->deltas[$name];
        }

        return $list;
    }
}
