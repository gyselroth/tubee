<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class StringLanguageProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions()
    {
        return [
            ExpressionFunction::fromPhp('strtolower'),
            ExpressionFunction::fromPhp('strtoupper'),
            ExpressionFunction::fromPhp('trim'),
            ExpressionFunction::fromPhp('sha1'),
            ExpressionFunction::fromPhp('md5'),
            ExpressionFunction::fromPhp('ucfirst'),
            ExpressionFunction::fromPhp('lcfirst'),
            ExpressionFunction::fromPhp('ltrim'),
            ExpressionFunction::fromPhp('rtrim'),
            ExpressionFunction::fromPhp('json_encode'),
            ExpressionFunction::fromPhp('json_decode'),
            ExpressionFunction::fromPhp('base64_encode'),
            ExpressionFunction::fromPhp('base64_decode'),
            ExpressionFunction::fromPhp('serialize'),
            ExpressionFunction::fromPhp('unserialize'),
            ExpressionFunction::fromPhp('str_replace'),
            ExpressionFunction::fromPhp('substr'),
            ExpressionFunction::fromPhp('pack'),
            ExpressionFunction::fromPhp('time', 'now'),
            $this->utf16(),
            $this->uuidv4(),
        ];
    }

    /**
     * Convert to UTF16.
     */
    protected function utf16(): ExpressionFunction
    {
        return new ExpressionFunction('utf16', function ($str) {
            return sprintf('$len = strlen(%1$s); $new=""; for ($i = 0; $i < $len; ++$i) {$new .= "{$string[$i]}\000";}', $str);
        }, function ($arguments, $str) {
            $len = strlen($str);
            $new = '';

            for ($i = 0; $i < $len; ++$i) {
                $new .= "{$str[$i]}\000";
            }

            return $new;
        });
    }

    /**
     * Convert to UTF16.
     */
    protected function uuidv4(): ExpressionFunction
    {
        return new ExpressionFunction('uuidv4', function () {
            return '';
        }, function () {
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
        });
    }
}
