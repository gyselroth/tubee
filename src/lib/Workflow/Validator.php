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

        if (!isset($resource['data']['ensure']) || !in_array($resource['data']['ensure'], WorkflowInterface::VALID_ENSURES)) {
            throw new InvalidArgumentException('data.ensure as string must be provided (one of exists,last,disabled,absent)');
        }

        if (isset($resource['data']['condition'])) {
            if (!is_string($resource['data']['condition'])) {
                throw new InvalidArgumentException('provided data.condition must be a string');
            }
        }

        if (!is_int($resource['data']['priority'])) {
            throw new InvalidArgumentException('provided data.priority must be an integer');
        }

        $resource['data']['map'] = AttributeMapValidator::validate($resource['data']['map']);

        return $resource;
    }
}
