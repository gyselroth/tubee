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
use MongoDB\BSON\ObjectIdInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rs\Json\Patch;
use Tubee\Acl;
use Tubee\DataObject\Factory as DataObjectFactory;
use Tubee\DataType\Factory as DataTypeFactory;
use Tubee\Mandator\Factory as MandatorFactory;
use Tubee\Rest\Helper;
use Tubee\Rest\Pager;
use Zend\Diactoros\Response;

class Objects
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
     * Object factory.
     *
     * @var DataObjectFactory
     */
    protected $object_factory;

    /**
     * Acl.
     *
     * @var Acl
     */
    protected $acl;

    /**
     * Init.
     */
    public function __construct(MandatorFactory $mandator_factory, DataTypeFactory $datatype_factory, DataObjectFactory $object_factory, Acl $acl)
    {
        $this->mandator_factory = $mandator_factory;
        $this->datatype_factory = $datatype_factory;
        $this->acl = $acl;
        $this->object_factory = $object_factory;
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

        $datatype = $this->mandator_factory->getOne($mandator)->getDataType($datatype);
        $objects = $datatype->getObjects($query['query'], false, (int) $query['offset'], (int) $query['limit']);

        return Helper::getAll($request, $identity, $this->acl, $objects);
    }

    /**
     * Entrypoint.
     */
    public function getOne(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype, ObjectIdInterface $object): ResponseInterface
    {
        $datatype = $this->mandator_factory->getOne($mandator)->getDataType($datatype);
        $object = $datatype->getObject(['_id' => $object], false);

        return Helper::getOne($request, $identity, $object);
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

        $datatype = $this->mandator_factory->getOne($mandator)->getDataType($datatype);
        $id = $datatype->createObject($body['data'], false, $body['endpoints']);

        if ($query['write'] === true) {
            //add job
        }

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_CREATED),
            $datatype->getObject(['_id' => $id], false)->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Entrypoint.
     */
    public function getHistory(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype, ObjectIdInterface $object): ResponseInterface
    {
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
            'query' => [],
        ], $request->getQueryParams());

        $datatype = $this->mandator_factory->getOne($mandator)->getDataType($datatype);
        $object = $datatype->getObject(['_id' => $object], false);
        $history = $object->getHistory();
        $body = Pager::fromRequest($history, $request);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $body,
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Patch.
     */
    public function patch(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype, ObjectIdInterface $object): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();
        $mandator = $this->mandator_factory->getOne($mandator);
        $datatype = $mandator->getDataType($datatype);
        $object = $datatype->getObject(['_id' => $object]);
        $doc = $object->getData();

        $patch = new Patch(json_encode($doc), json_encode($body));
        $patched = $patch->apply();
        $update = json_decode($patched, true);

        $this->object_factory->update($object, $update);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $datatype->getObject(['_id' => $object->getId()])->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Watch.
     */
    public function watchAll(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype): ResponseInterface
    {
        $datatype = $this->mandator_factory->getOne($mandator)->getDataType($datatype);
        $cursor = $this->object_factory->watch($datatype);

        return Helper::watchAll($request, $identity, $this->acl, $cursor);
    }
}
