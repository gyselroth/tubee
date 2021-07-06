<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2021 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Rest\Middlewares;

use Micro\Auth\Middleware\Auth as MicroAuth;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Auth extends MicroAuth
{
    /**
     * Process a server request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $target = $request->getRequestTarget();

        $skip = [
            '/healthz',
            '/openapi/v2',
            '/openapi/v3',
            '/api',
            '/api/v1',
        ];

        if (in_array($target, $skip)) {
            return $handler->handle($request);
        }

        return parent::process($request, $handler);
    }
}
