<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copyright (c) 2012-2022 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Rest\Middlewares;

use Fig\Http\Message\StatusCodeInterface;
use Zend\Diactoros\Response;
use Lcobucci\ContentNegotiation\UnformattedResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CorsHandler implements MiddlewareInterface
{
    /**
     * Process a server request and return a response.
     *
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestHeaders = $request->getHeaders();

        if (!isset($requestHeaders['origin'])) {
            return $handler->handle($request);
        }

        $requestMethod = $request->getMethod();

        if ($requestMethod === 'OPTIONS' &&
            isset($requestHeaders['access-control-request-method']) &&
            isset($requestHeaders['access-control-request-headers'])) {
            return new UnformattedResponse(
                (new Response())->withStatus(StatusCodeInterface::STATUS_NO_CONTENT)
                    ->withHeader('Access-Control-Allow-Origin', '*')
                    ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                    ->withHeader('Access-Control-Allow-Headers', 'Authorization')
                    ->withHeader('Access-Control-Max-Age', '86400'),
                []
            );
        }

        return $handler->handle($request)
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}
