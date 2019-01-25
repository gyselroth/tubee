<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Workflow;

use InvalidArgumentException;
use Tubee\AttributeMap\Validator as AttributeMapValidator;
use Tubee\Resource\Validator as ResourceValidator;

class Validator extends ResourceValidator
{
    /**
     * Validate resource.
     */
    public static function validateWorkflow(array $resource): array
    {
        $resource = parent::validate($resource);
        $defaults = [
            'data' => [
                'ensure' => WorkflowInterface::ENSURE_LAST,
                'priority' => 0,
                'map' => [],
                'condition' => null,
            ],
        ];

        $resource = array_replace_recursive($defaults, $resource);

        foreach ($resource['data'] as $key => $value) {
            switch ($key) {
                case 'ensure':
                    if (!in_array($value, WorkflowInterface::VALID_ENSURES)) {
                        throw new InvalidArgumentException('data.ensure as string must be provided (one of exists,last,disabled,absent)');
                    }

                break;
                case 'condition':
                    if ($value !== null && !is_string($value)) {
                        throw new InvalidArgumentException('provided data.condition must be a string');
                    }

                break;
                case 'priority':
                    if (!is_int($value)) {
                        throw new InvalidArgumentException('provided data.priority must be an integer');
                    }

                break;
                case 'map':
                    $resource['data']['map'] = AttributeMapValidator::validate($value);

                break;
                default:
                    throw new InvalidArgumentException('unknown option '.$key.' provided');
            }
        }

        return $resource;
    }
}
