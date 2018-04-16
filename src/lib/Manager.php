<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use Psr\Log\LoggerInterface;
use Tubee\Manager\Exception;
use Tubee\Mandator\MandatorInterface;

class Manager
{
    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Mandators.
     *
     * @var array
     */
    protected $mandators = [];

    /**
     * Initialize.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Has mandator.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasMandator(string $name): bool
    {
        return isset($this->mandators[$name]);
    }

    /**
     * Inject mandator.
     *
     * @param MandatorInterface $mandator
     *
     * @return Manager
     */
    public function injectMandator(MandatorInterface $mandator, string $name): self
    {
        $this->logger->debug('inject mandator ['.$name.'] of type ['.get_class($mandator).']', [
            'category' => get_class($this),
        ]);

        if ($this->hasMandator($name)) {
            throw new Exception\MandatorNotUnique('mandator '.$name.' is already registered');
        }

        $this->mandators[$name] = $mandator;

        return $this;
    }

    /**
     * Get mandator.
     *
     * @param string $name
     *
     * @return MandatorInterface
     */
    public function getMandator(string $name): MandatorInterface
    {
        if (!$this->hasMandator($name)) {
            throw new Exception\MandatorNotFound('mandator '.$name.' is not registered');
        }

        return $this->mandators[$name];
    }

    /**
     * Get mandators.
     *
     * @param array $mandators
     *
     * @return MandatorInterface[]
     */
    public function getMandators(array $mandators = []): array
    {
        if (count($mandators) === 0) {
            return $this->mandators;
        }
        $list = [];
        foreach ($mandators as $name) {
            if (!$this->hasMandator($name)) {
                throw new Exception\MandatorNotFound('mandator '.$name.' is not registered');
            }
            $list[$name] = $this->mandators[$name];
        }

        return $list;
    }
}
