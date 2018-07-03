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
use Tubee\Mandator\MandatorInterface;
use Tubee\MandatorManager\Exception;

class MandatorManager
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
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Has mandator.
     */
    public function hasMandator(string $name): bool
    {
        return isset($this->mandators[$name]);
    }

    /**
     * Inject mandator.
     *
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
