<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Rest;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tubee\Acl as CoreAcl;

class Acl implements MiddlewareInterface
{
    /**
     * Acl.
     *
     * @var CoreAcl
     */
    protected $acl;

    /**
     * Set the resolver instance.
     */
    public function __construct(CoreAcl $acl)
    {
        $this->acl = $acl;
    }

    /**
     * Process a server request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->acl->isAllowed($request, $request->getAttribute('identity'))) {
            return $handler->handle($request);
        }
    }
}
