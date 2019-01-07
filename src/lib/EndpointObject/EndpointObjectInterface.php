<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\EndpointObject;

use Tubee\Endpoint\EndpointInterface;
use Tubee\Resource\ResourceInterface;

interface EndpointObjectInterface extends ResourceInterface
{
    /**
     * Get data.
     */
    public function getData(): array;

    /**
     * Get endpoint.
     */
    public function getEndpoint(): EndpointInterface;
}
