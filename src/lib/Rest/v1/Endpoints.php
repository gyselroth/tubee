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
use Tubee\DataType\Factory as DataTypeFactory;
use Tubee\Endpoint\Factory as EndpointFactory;
use Tubee\Mandator\Factory as MandatorFactory;
use Tubee\Rest\Pager;
use Zend\Diactoros\Response;

class Endpoints
{
    /**
     * Init.
     */
    public function __construct(MandatorFactory $mandator, DataTypeFactory $datatype, EndpointFactory $endpoint, Acl $acl)
    {
        $this->mandator = $mandator;
        $this->datatype = $datatype;
        $this->endpoint = $endpoint;
        $this->acl = $acl;
    }

    /**
     * Entrypoint.
     */
    public function getAll(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype): ResponseInterface
    {
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
            'query' => [],
        ], $request->getQueryParams());

        $mandator = $this->mandator->getOne($mandator);
        $datatype = $this->datatype->getOne($mandator, $datatype);
        $endpoints = $this->endpoint->getAll($datatype, $query['query'], (int) $query['offset'], (int) $query['limit']);

        $body = $this->acl->filterOutput($request, $identity, $endpoints);
        $body = Pager::fromRequest($body, $request);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $body,
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Entrypoint.
     */
    public function getOne(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype, string $endpoint): ResponseInterface
    {
        $query = $request->getQueryParams();

        $mandator = $this->mandator->getOne($mandator);
        $datatype = $this->datatype->getOne($mandator, $datatype);
        $endpoint = $this->endpoint->getAll($datatype, $endpoint);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $endpoint->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Create.
     */
    public function post(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype): ResponseInterface
    {
        $body = $request->getParsedBody();

        $mandator = $this->mandator->getOne($mandator);
        $datatype = $this->datatype->getOne($mandator, $datatype);
        $id = $this->endpoint->add($datatype, $body);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_CREATED),
            $this->endpoint->getOne($datatype, $body['name'])->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }
}
