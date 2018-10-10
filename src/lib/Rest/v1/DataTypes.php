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
use Tubee\Mandator\Factory as MandatorFactory;
use Tubee\Rest\Pager;
use Zend\Diactoros\Response;

class DataTypes
{
    /**
     * Init.
     */
    public function __construct(MandatorFactory $mandator_factory, DataTypeFactory $datatype_factory, Acl $acl)
    {
        $this->mandator_factory = $mandator_factory;
        $this->datatype_factory = $datatype_factory;
        $this->acl = $acl;
    }

    /**
     * Entrypoint.
     */
    public function getAll(ServerRequestInterface $request, Identity $identity, string $mandator): ResponseInterface
    {
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
            'query' => [],
        ], $request->getQueryParams());

        $mandator = $this->mandator_factory->getOne($mandator);
        $datatypes = $mandator->getDataTypes($query['query'], (int) $query['offset'], (int) $query['limit']);

        $body = $this->acl->filterOutput($request, $identity, $datatypes);
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
    public function getOne(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype): ResponseInterface
    {
        $query = $request->getQueryParams();

        $mandator = $this->mandator_factory->getOne($mandator);
        $datatype = $mandator->getDataType($datatype);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $datatype->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Create.
     */
    public function post(ServerRequestInterface $request, Identity $identity, string $mandator): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();

        $mandator = $this->mandator_factory->getOne($mandator);
        $id = $this->datatype_factory->add($mandator, $body);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_CREATED),
            $this->datatype_factory->getOne($mandator, $body['name'])->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }
}
