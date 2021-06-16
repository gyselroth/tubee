<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2021 gyselroth GmbH (https://gyselroth.com)
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
     * Kind.
     */
    public const KIND = 'Log';

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
            'created' => $this->resource['changed']->toDateTime()->format('c'),
            'changed' => $this->resource['changed']->toDateTime()->format('c'),
            'data' => $this->getData(),
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
