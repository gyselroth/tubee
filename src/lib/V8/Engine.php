<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2022 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\V8;

use Psr\Log\LoggerInterface;
use V8Js;

class Engine extends V8Js
{
    protected $__result;

    /**
     * Create v8 engine with some default functionality.
     */
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct('core', [], [], true, '');
        $this->logger = $logger;
        $this->registerFunctions();
        $this->setMemoryLimit(50000000);
    }

    public function result($result)
    {
        $this->__result = $result;
    }

    public function getLastResult()
    {
        $result = $this->__result;
        $this->__result = null;

        return $result;
    }

    /**
     * Convert to UTF16.
     */
    public function utf16(string $string): string
    {
        $len = strlen($string);
        $new = '';

        for ($i = 0; $i < $len; ++$i) {
            $new .= "{$string[$i]}\000";
        }

        return $new;
    }

    /**
     * Create UUIDv4.
     */
    public function uuidv4(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Register functions.
     */
    protected function registerFunctions(): void
    {
        $this->crypt = [
            'hash' => function ($algo, $data, $raw_output = false) {
                return hash($algo, $data, $raw_output);
            },
        ];
    }
}
