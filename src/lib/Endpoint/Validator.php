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
use Tubee\Resource\Validator as ResourceValidator;

class Validator extends ResourceValidator
{
    /**
     * Validate resource.
     */
    public static function validate(array $resource): array
    {
        $resource = parent::validate($resource);

        if (!isset($resource['type']) || $resource['type'] !== 'destination' && $resource['type'] !== 'source') {
            throw new InvalidArgumentException('Either destination or source must be provided as type');
        }

        if ($resource['type'] === EndpointInterface::TYPE_SOURCE && (!is_array($resource['data_options']['import']) || count($resource['data_options']['import']) === 0)) {
            throw new InvalidArgumentException('source endpoint must include at least one data_options.import attribute');
        }

        if ($resource['type'] === EndpointInterface::TYPE_DESTINATION && (!isset($resource['data_options']['filter_one']) || !is_string($resource['data_options']['filter_one']))) {
            throw new InvalidArgumentException('destintation endpoint must have single object filter data_options.filter_one');
        }

        if (!isset($resource['class']) || !is_string($resource['class'])) {
            throw new InvalidArgumentException('class as string must be provided');
        }

        if (!isset($resource['resource']) || !is_array($resource['resource'])) {
            throw new InvalidArgumentException('resource as array must be provided');
        }

        //parent::allowOnly($resource, ['type','class','import', 'resource']);

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
        if (class_exists($validator)) {
            return $resource = $validator::validate($resource);
        }

        return $resource;
    }
}
