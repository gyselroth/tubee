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
use MongoDB\BSON\ObjectId;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tubee\Acl;
use Tubee\DataType\Factory as DataTypeFactory;
use Tubee\Mandator\Factory as MandatorFactory;
use Tubee\Rest\Pager;
use Zend\Diactoros\Response;

class Objects
{
    /**
     * Init.
     */
    public function __construct(MandatorFactory $mandator, DataTypeFactory $datatype, Acl $acl)
    {
        $this->mandator = $mandator;
        $this->datatype = $datatype;
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
        $objects = $datatype->getAll($query['query'], false, (int) $query['offset'], (int) $query['limit']);

        $body = $this->acl->filterOutput($request, $identity, $objects);
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
    public function getOne(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype, ObjectId $object): ResponseInterface
    {
        $query = $request->getQueryParams();

        $mandator = $this->mandator->getOne($mandator);
        $datatype = $this->datatype->getOne($mandator, $datatype);
        $object = $datatype->getOne(['_id' => $object], false);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $object->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Create object.
     */
    public function post(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype): ResponseInterface
    {
        $query = array_merge([
            'write' => false,
        ], $request->getQueryParams());

        $body = array_merge([
            'data' => [],
            'endpoints' => null,
        ], $request->getParsedBody());

        $mandator = $this->mandator->getOne($mandator);
        $datatype = $this->datatype->getOne($mandator, $datatype);
        $id = $datatype->create($body['data'], false, $body['endpoints']);

        if ($query['write'] === true) {
            //add job
        }

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_CREATED),
            $datatype->getOne(['_id' => $id], false)->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Entrypoint.
     */
    public function getHistory(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype, ObjectId $object): ResponseInterface
    {
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
            'query' => [],
        ], $request->getQueryParams());

        $mandator = $this->mandator->getOne($mandator);
        $datatype = $this->datatype->getOne($mandator, $datatype);
        $object = $datatype->getOne(['_id' => $object], false);
        $history = $object->getHistory();
        $body = Pager::fromRequest($history, $request);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $body,
            ['pretty' => isset($query['pretty'])]
        );
    }
}
