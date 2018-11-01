<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Job;

use InvalidArgumentException;
use Monolog\Logger;
use TaskScheduler\SchedulerValidator;
use Tubee\Resource\Validator as ResourceValidator;

class Validator extends ResourceValidator
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
     * Validate resource.
     */
    public static function validate(array $resource): array
    {
        $defaults = [
            'data' => [
            'notification' => [
                'enabled' => false,
                'receiver' => [],
            ],
            'mandators' => [],
            'datatypes' => [],
            'endpoints' => [],
            'filter' => [],
            'loadbalance' => true,
            'simulate' => false,
            'log_level' => 'error',
            'ignore' => false,
            'options' => [
                'at' => 0,
                'interval' => 0,
                'retry' => 0,
                'retry_interval' => 0,
                'timeout' => 0,
            ],
            ],
        ];

        $resource = array_replace_recursive($defaults, $resource);
        $resource = parent::validate($resource);

        foreach ($resource['data'] as $option => $value) {
            switch ($option) {
                case 'mandators':
                case 'datatypes':
                case 'endpoints':
                    if (!is_array($value) || count(array_filter($value, 'is_string')) !== count($value)) {
                        throw new InvalidArgumentException('option '.$option.' must be an array of resource names');
                    }

                break;

                break;
                case 'loadbalance':
                case 'simulate':
                case 'ignore':
                    if (!is_bool($value)) {
                        throw new InvalidArgumentException('option '.$option.' must be a boolean value');
                    }

                break;
                case 'options':
                    SchedulerValidator::validateOptions($value);

                break;
                case 'filter':
                    if (!is_array($value)) {
                        throw new InvalidArgumentException('option '.$option.' must be an array');
                    }

                break;
                case 'log_level':
                    if (!isset(self::LOG_LEVELS[$value])) {
                        throw new InvalidArgumentException('invalid log_level provided (one of '.implode(',', self::LOG_LEVELS).')');
                    }

                break;
                case 'notification':
                    if (!is_bool($value['enabled'])) {
                        throw new InvalidArgumentException('option notification.enabled must be a boolean value');
                    }

                    if (!is_array($value['receiver'])) {
                        throw new InvalidArgumentException('option notification.receiver must be an array of mail addresses');
                    }

                    $result = filter_var_array($value['receiver'], FILTER_VALIDATE_EMAIL);
                    if (!is_array($value['receiver']) || in_array(false, $result)) {
                        throw new InvalidArgumentException('option notification.receiver must be an array of mail addresses');
                    }

                break;
                default:
                    if (!in_array($option, self::RESOURCE_ATTRIBUTES)) {
                        throw new InvalidArgumentException('invalid option '.$option.' provided');
                    }
            }
        }

        return $resource;
    }
}
