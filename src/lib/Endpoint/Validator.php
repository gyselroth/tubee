<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use InvalidArgumentException;

class Validator
{
    /**
     * Validate resource.
     */
    public static function validate(array $resource): array
    {
        if (!isset($resource['name']) || !is_string($resource['name'])) {
            throw new InvalidArgumentException('A name as string must be provided');
        }

        if (isset($resource['description']) && !is_string($resource['description'])) {
            throw new InvalidArgumentException('Description must be a string');
        }

        if (!isset($resource['type']) || $resource['type'] !== 'destination' && $resource['type'] !== 'source') {
            throw new InvalidArgumentException('Either destination or source must be provided as type');
        }

        if ($resource['type'] === EndpointInterface::TYPE_SOURCE && $resource['import'] === 0) {
            throw new InvalidArgumentException('source endpoint must include at least one import condition');
        }

        if (!isset($resource['class']) || !is_string($resource['class'])) {
            throw new InvalidArgumentException('A class as string must be provided');
        }

        return self::validateEndpoint($resource);
    }

    /**
     * Validate endpoint.
     */
    protected static function validateEndpoint(array $resource): array
    {
        if (strpos($resource['class'], '\\') === false) {
            $class = 'Tubee\\Endpoint\\'.$resource['class'];
        } else {
            $class = $resource['class'];
        }

        if (!class_exists($class)) { //|| !class_exists($resource['class'].'\\Validator')) {
            throw new InvalidArgumentException("Endpoint $class does not exists");
        }

        $resource['class'] = $class;
        $validator = $class.'\\Validator';

        return $validator::validate($resource);
    }
}
