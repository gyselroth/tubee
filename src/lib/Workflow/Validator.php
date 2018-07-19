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

class Validator
{
    /**
     * Validate resource.
     */
    public static function validate(array $resource): array
    {
        if (!isset($resource['ensure']) || !in_array($resource['ensure'], WorkflowInterface::VALID_ENSURES)) {
            throw new InvalidArgumentException('spec.ensure as string must be provided (One of exists,last,disabled,absent)');
        }

        if (!isset($resource['map'])) {
            throw new InvalidArgumentException('spec.map must be provided');
        }

        AttributeMapValidator::validate($resource['map']);

        return $resource;
    }
}
