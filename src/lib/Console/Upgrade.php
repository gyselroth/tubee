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
use Psr\Log\LoggerInterface;
use Tubee\Migration;

class Upgrade
{
    /**
     * Getopt.
     *
     * @var GetOpt
     */
    protected $getopt;

    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Migration.
     *
     * @var Migration
     */
    protected $migration;

    /**
     * Constructor.
     */
    public function __construct(Migration $migration, LoggerInterface $logger, GetOpt $getopt)
    {
        $this->migration = $migration;
        $this->logger = $logger;
        $this->getopt = $getopt;
    }

    /**
     * Start.
     */
    public function __invoke(): bool
    {
        $deltas = $this->getopt->getOption('delta');
        if ($deltas === null) {
            $deltas = [];
        } else {
            $deltas = explode(',', $deltas);
        }

        return $this->migration->start(
            (bool) $this->getopt->getOption('force'),
            (bool) $this->getopt->getOption('ignore'),
            $deltas
        );
    }

    // Get operands
    public static function getOperands(): array
    {
        return [];
    }

    /**
     * Get upgrade options.
     */
    public static function getOptions(): array
    {
        return [
            \GetOpt\Option::create('f', 'force')->setDescription('Force apply deltas even if a delta has already been applied before'),
            \GetOpt\Option::create('i', 'ignore')->setDescription('Do not abort if any error is encountered'),
            \GetOpt\Option::create('d', 'delta', \GetOpt\GetOpt::REQUIRED_ARGUMENT)->setDescription('Specify specific deltas (comma separated)'),
        ];
    }
}
