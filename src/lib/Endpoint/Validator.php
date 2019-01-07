<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
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

        $defaults = [
            'data' => [
                'type' => EndpointInterface::TYPE_BROWSE,
                'options' => [
                    'flush' => false,
                    'import' => [],
                    'filter_one' => null,
                    'filter_all' => null,
                ],
                'resource' => [],
            ],
        ];

        if (!isset($resource['kind']) || !isset(EndpointInterface::ENDPOINT_MAP[$resource['kind']])) {
            throw new InvalidArgumentException('invalid endpoint kind provided, provide one of ['.join(',', array_flip(EndpointInterface::ENDPOINT_MAP)).']');
        }

        $resource = array_replace_recursive($defaults, $resource);
        if (!in_array($resource['data']['type'], EndpointInterface::VALID_TYPES)) {
            throw new InvalidArgumentException('invalid endpoint type provided, provide one of ['.join(',', EndpointInterface::VALID_TYPES).']');
        }

        if ($resource['data']['type'] === EndpointInterface::TYPE_SOURCE && (!is_array($resource['data']['options']['import']) || count($resource['data']['options']['import']) === 0)) {
            throw new InvalidArgumentException('source endpoint must include at least one options.import attribute');
        }

        if ($resource['data']['type'] === EndpointInterface::TYPE_DESTINATION && (!isset($resource['data']['options']['filter_one']) || !is_string($resource['data']['options']['filter_one']))) {
            throw new InvalidArgumentException('destintation endpoint must have single object filter options.filter_one as a string');
        }

        if (!is_array($resource['data']['resource'])) {
            throw new InvalidArgumentException('resource as array must be provided');
        }

        return self::validateEndpoint($resource);
    }

    /**
     * Validate endpoint.
     */
    protected static function validateEndpoint(array $resource): array
    {
        $class = EndpointInterface::ENDPOINT_MAP[$resource['kind']];
        $validator = $class.'\\Validator';
        if (class_exists($validator)) {
            $resource['data'] = $validator::validate($resource['data']);

            return $resource;
        }

        return $resource;
    }
}
