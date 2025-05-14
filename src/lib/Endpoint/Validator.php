<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use InvalidArgumentException;
use Tubee\Helper;

class Validator
{
    /**
     * Validate resource.
     */
    public static function validate(array $resource): array
    {
        if ($resource['data']['type'] === EndpointInterface::TYPE_SOURCE && (!is_array($resource['data']['options']['import']) || count($resource['data']['options']['import']) === 0)) {
            throw new InvalidArgumentException('source endpoint must include at least one options.import attribute');
        }

        if ($resource['data']['type'] === EndpointInterface::TYPE_DESTINATION && !isset($resource['data']['options']['filter_one'])) {
            throw new InvalidArgumentException('destintation endpoint must have single object filter options.filter_one as a string');
        }

        if (isset($resource['data']['options']['filter_one'])) {
            Helper::jsonDecode(stripslashes($resource['data']['options']['filter_one']), true);
        }

        if (isset($resource['data']['options']['filter_all'])) {
            Helper::jsonDecode(stripslashes($resource['data']['options']['filter_all']), true);
        }

        return $resource;
    }
}
