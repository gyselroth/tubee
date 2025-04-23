<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Rest\v1;

use Fig\Http\Message\StatusCodeInterface;
use Lcobucci\ContentNegotiation\UnformattedResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;

class Api
{
    /**
     * Entrypoint.
     */
    public function get(ServerRequestInterface $request): ResponseInterface
    {
        $data = [
            '_links' => [
                'self' => ['href' => (string) $request->getUri()],
            ],
            'name' => 'tubee',
            'version' => 1,
        ];

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $data
        );
    }
}
