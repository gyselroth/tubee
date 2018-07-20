<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
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
    public static function validate(array $resource): array
    {
        $resource = parent::validate($resource);

        if (!isset($resource['ensure']) || !in_array($resource['ensure'], WorkflowInterface::VALID_ENSURES)) {
            throw new InvalidArgumentException('ensure as string must be provided (one of exists,last,disabled,absent)');
        }

        if (!isset($resource['map'])) {
            throw new InvalidArgumentException('map must be provided');
        }

        if (isset($resource['condition']) && !is_string($resource['condition'])) {
            throw new InvalidArgumentException('provided condition must be a string');
        }

        parent::allowOnly($resource, ['ensure', 'condition', 'map']);
        AttributeMapValidator::validate($resource['map']);

        return $resource;
    }
}
