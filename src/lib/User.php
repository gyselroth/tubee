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
use Tubee\Resource\AbstractResource;
use Tubee\Resource\AttributeResolver;
use Tubee\User\UserInterface;

class User extends AbstractResource implements UserInterface
{
    /**
     * Initialize.
     */
    public function __construct(array $resource = [])
    {
        $this->resource = $resource;
    }

    /**
     * Validate password.
     */
    public function validatePassword(string $password): bool
    {
        return password_verify($password, $this->resource['hash']);
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
            'kind' => 'User',
        ];

        return AttributeResolver::resolve($request, $this, $resource);
    }
}
