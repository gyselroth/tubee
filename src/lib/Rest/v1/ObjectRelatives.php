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
use Tubee\Acl;
use Tubee\DataObjectRelation\Factory as DataObjectRelationFactory;
use Tubee\Mandator\Factory as MandatorFactory;
use Tubee\Rest\Helper;
use Zend\Diactoros\Response;

class ObjectRelatives
{
    /**
     * mandator factory.
     *
     * @var MandatorFactory
     */
    protected $mandator_factory;

    /**
     * Dataobject relation factory.
     *
     * @var DataObjectRelationFactory
     */
    protected $relation_factory;

    /**
     * Acl.
     *
     * @var Acl
     */
    protected $acl;

    /**
     * Init.
     */
    public function __construct(MandatorFactory $mandator_factory, DataObjectRelationFactory $relation_factory, Acl $acl)
    {
        $this->mandator_factory = $mandator_factory;
        $this->relation_factory = $relation_factory;
        $this->acl = $acl;
    }

    /**
     * Entrypoint.
     */
    public function getAll(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype, ObjectIdInterface $object): ResponseInterface
    {
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
            'query' => [],
        ], $request->getQueryParams());

        $datatype = $this->mandator_factory->getOne($mandator)->getDataType($datatype);
        $object = $datatype->getObject(['_id' => $object]);
        $relatives = $object->getRelatives($query['query'], false, (int) $query['offset'], (int) $query['limit']);

        return Helper::getAll($request, $identity, $this->acl, $relatives);
    }

    /**
     * Entrypoint.
     */
    public function getOne(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype, ObjectIdInterface $object, ObjectIdInterface $relative): ResponseInterface
    {
        $datatype = $this->mandator_factory->getOne($mandator)->getDataType($datatype);
        $object = $datatype->getObject(['_id' => $object], false);
        $relative = $object->getRelative($relative);

        return Helper::getOne($request, $identity, $relative);
    }

    /**
     * Create object.
     */
    public function post(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype, ObjectIdInterface $object): ResponseInterface
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
            $datatype->getOne(['_id' => $id], false)->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Watch.
     */
    public function watchAll(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype, ObjectIdInterface $id): ResponseInterface
    {
        $object = $this->mandator_factory->getOne($mandator)->getDataType($datatype)->getObject($id);
        $cursor = $this->relation_factory->watch($object);

        return Helper::watchAll($request, $identity, $this->acl, $cursor);
    }
}
