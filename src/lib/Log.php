<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use Psr\Http\Message\ServerRequestInterface;
use Tubee\Log\LogInterface;
use Tubee\Resource\AbstractResource;
use Tubee\Resource\AttributeResolver;

class Log extends AbstractResource implements LogInterface
{
    /**
     * Data object.
     */
    public function __construct(array $resource)
    {
        $this->resource = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(ServerRequestInterface $request): array
    {
        $response = [
            '_links' => [
            ],
            'kind' => 'Log',
            'created' => $this->resource['datetime']->toDateTime()->format('c'),
            'changed' => $this->resource['datetime']->toDateTime()->format('c'),
            'data' => [
                'level' => $this->resource['level'],
                'level_name' => $this->resource['level_name'],
                'message' => $this->resource['message'],
                'category' => $this->resource['context']['category'],
            ],
        ];

        if (isset($this->resource['context']['exception'])) {
            $response['data']['exception'] = $this->resource['context']['exception'];
        }

        if (isset($this->resource['context']['object'])) {
            $response['data']['object'] = $this->resource['context']['object'];
        }

        return AttributeResolver::resolve($request, $this, $response);
    }
}
