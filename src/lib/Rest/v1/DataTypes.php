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
use Rs\Json\Patch;
use Tubee\Acl;
use Tubee\DataType\Factory as DataTypeFactory;
use Tubee\Mandator\Factory as MandatorFactory;
use Tubee\Rest\Helper;
use Zend\Diactoros\Response;

class DataTypes
{
    /**
     * mandator factory.
     *
     * @var MandatorFactory
     */
    protected $mandator_factory;

    /**
     * datatype factory.
     *
     * @var DataTypeFactory
     */
    protected $datatype_factory;

    /**
     * Acl.
     *
     * @var Acl
     */
    protected $acl;

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

        return Helper::getAll($request, $identity, $this->acl, $datatypes);
    }

    /**
     * Entrypoint.
     */
    public function getOne(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype): ResponseInterface
    {
        $mandator = $this->mandator_factory->getOne($mandator);
        $datatype = $mandator->getDataType($datatype);

        return Helper::getOne($request, $identity, $datatype);
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

    /**
     * Patch.
     */
    public function patch(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();
        $mandator = $this->mandator_factory->getOne($mandator);
        $datatype = $mandator->getDataType($datatype);
        $doc = $datatype->getData();

        $patch = new Patch(json_encode($doc), json_encode($body));
        $patched = $patch->apply();
        $update = json_decode($patched, true);

        $this->datatype_factory->update($datatype, $update);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $this->datatype_factory->getOne($datatype->getName())->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Watch.
     */
    public function watchAll(ServerRequestInterface $request, Identity $identity, string $mandator): ResponseInterface
    {
        $mandator = $this->mandator_factory->getOne($mandator);
        $cursor = $this->datatype_factory->watch($mandator);

        return Helper::watchAll($request, $identity, $this->acl, $cursor);
    }
}
