<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Rest\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tubee\Rest\Exception;
use Zend\Diactoros\Response;

class QueryDecoder implements MiddlewareInterface
{
    /**
     * Process a server request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $query = $request->getQueryParams();
        if (isset($query['query'])) {
            $query['query'] = json_decode(htmlspecialchars_decode($query['query']), true);

            if (json_last_error()) {
                throw new Exception\InvalidInput('failed to decode provided query: '.json_last_error_msg().', query needs to be valid json');
            }

            $request = $request->withQueryParams($query);
        }

        return $handler->handle($request);
    }
}
