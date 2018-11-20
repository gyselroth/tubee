<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use Psr\Http\Message\ServerRequestInterface;
use Tubee\Resource\AbstractResource;
use Tubee\Resource\AttributeResolver;
use Tubee\Secret\SecretInterface;

class Secret extends AbstractResource implements SecretInterface
{
    /**
     * Name.
     *
     * @var string
     */
    protected $name;

    /**
     * Initialize.
     */
    public function __construct(array $resource = [])
    {
        $this->resource = $resource;
    }

    /**
     * Decorate.
     */
    public function decorate(ServerRequestInterface $request): array
    {
        $resource = [
            '_links' => [
                'self' => ['href' => (string) $request->getUri()],
            ],
            'kind' => 'Secret',
        ];

        return AttributeResolver::resolve($request, $this, $resource);
    }
}
