<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Console;

use GetOpt\GetOpt;
use ParagonIE\Halite\KeyFactory;

class Key
{
    /**
     * Getopt.
     *
     * @var GetOpt
     */
    protected $getopt;

    /**
     * Constructor.
     */
    public function __construct(GetOpt $getopt)
    {
        $this->getopt = $getopt;
    }

    /**
     * Start.
     */
    public function __invoke(): bool
    {
        $key = KeyFactory::export(KeyFactory::generateEncryptionKey());
        echo $key->getString()."\n";

        return true;
    }

    // Get operands
    public static function getOperands(): array
    {
        return [];
    }

    /**
     * Get options.
     */
    public static function getOptions(): array
    {
        return [];
    }
}
