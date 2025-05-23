<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use Psr\Http\Message\ServerRequestInterface;
use Tubee\AccessRole\AccessRoleInterface;
use Tubee\Resource\AbstractResource;
use Tubee\Resource\AttributeResolver;

class AccessRole extends AbstractResource implements AccessRoleInterface
{
    /**
     * Kind.
     */
    public const KIND = 'AccessRole';

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
        $resource = [
            '_links' => [
                'self' => ['href' => (string) $request->getUri()],
            ],
            'kind' => 'AccessRole',
            'data' => $this->getData(),
        ];

        return AttributeResolver::resolve($request, $this, $resource);
    }
}
