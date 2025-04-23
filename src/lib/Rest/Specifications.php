<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Rest;

use Fig\Http\Message\StatusCodeInterface;
use Lcobucci\ContentNegotiation\UnformattedResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Yaml\Yaml;
use Zend\Diactoros\Response;

class Specifications
{
    /**
     * Get spec.
     */
    public function getApiv2(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        $data = $this->load(__DIR__.'/v1/swagger.yml');

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $data,
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Get spec.
     */
    public function getApiv3(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        $data = $this->load(__DIR__.'/v1/openapi.yml');

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $data,
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Retrieve spec.
     */
    protected function load(string $path): array
    {
        if (apcu_exists($path)) {
            return apcu_fetch($path);
        }

        $data = Yaml::parseFile($path);
        apcu_store($path, $data);

        return $data;
    }
}
