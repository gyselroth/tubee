<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Rest\v1;

use Fig\Http\Message\StatusCodeInterface;
use Lcobucci\ContentNegotiation\UnformattedResponse;
use Micro\Auth\Identity;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tubee\Acl;
use Tubee\MandatorManager;
use Tubee\Rest\Pager;
use Zend\Diactoros\Response;

class DataTypes
{
    /**
     * Init.
     */
    public function __construct(MandatorManager $manager, Acl $acl)
    {
        $this->manager = $manager;
        $this->acl = $acl;
    }

    /**
     * Entrypoint.
     */
    public function get(ServerRequestInterface $request, Identity $identity, string $mandator, ?string $datatype = null): ResponseInterface
    {
        $query = array_merge([
            'query' => [],
        ], $request->getQueryParams());

        if ($datatype !== null) {
            return new UnformattedResponse(
                (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
                $this->manager->getMandator($mandator)->getDataType($datatype)->decorate($request),
                ['pretty' => isset($query['pretty'])]
            );
        }

        $mandator = $this->manager->getMandator($mandator);
        $datatypes = $mandator->getDataTypes($query['query']);

        $body = $this->acl->filterOutput($request, $identity, $datatypes);
        $body = Pager::fromRequest($body, $request);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $body,
            ['pretty' => isset($query['pretty'])]
        );
    }
}
