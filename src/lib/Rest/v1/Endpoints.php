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
use Tubee\Endpoint\Factory as EndpointFactory;
use Tubee\Mandator\Factory as MandatorFactory;
use Tubee\Rest\Helper;
use Zend\Diactoros\Response;

class Endpoints
{
    /**
     * mandator factory.
     *
     * @var MandatorFactory
     */
    protected $mandator_factory;

    /**
     * Endpoint factory.
     *
     * @var EndpointFactory
     */
    protected $endpoint_factory;

    /**
     * Acl.
     *
     * @var Acl
     */
    protected $acl;

    /**
     * Init.
     */
    public function __construct(MandatorFactory $mandator_factory, EndpointFactory $endpoint_factory, Acl $acl)
    {
        $this->mandator_factory = $mandator_factory;
        $this->endpoint_factory = $endpoint_factory;
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

        $datatype = $this->mandator_factory->getOne($mandator)->getDataType($datatype);
        $endpoints = $datatype->getEndpoints($query['query'], (int) $query['offset'], (int) $query['limit']);

        return Helper::getAll($request, $identity, $this->acl, $endpoints);
    }

    /**
     * Entrypoint.
     */
    public function getOne(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype, string $endpoint): ResponseInterface
    {
        $datatype = $this->mandator_factory->getOne($mandator)->getDataType($datatype);
        $endpoint = $datatype->getEndpoint($endpoint);

        return Helper::getOne($request, $identity, $endpoint);
    }

    /**
     * Create.
     */
    public function post(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();

        $datatype = $this->mandator_factory->getOne($mandator)->getDataType($datatype);
        $id = $this->endpoint_factory->add($datatype, $body);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_CREATED),
            $this->endpoint_factory->getOne($datatype, $body['name'])->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Delete.
     */
    public function delete(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype, string $endpoint): ResponseInterface
    {
        $datatype = $this->mandator_factory->getOne($mandator)->getDataType($datatype);
        $this->endpoint_factory->deleteOne($datatype, $endpoint);

        return(new Response())->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);
    }

    /**
     * Patch.
     */
    public function patch(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype, string $endpoint): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();
        $mandator = $this->mandator_factory->getOne($mandator);
        $datatype = $mandator->getDataType($datatype);
        $endpoint = $datatype->getEndpoint($endpoint);
        $doc = $endpoint->getData();

        $patch = new Patch(json_encode($doc), json_encode($body));
        $patched = $patch->apply();
        $update = json_decode($patched, true);

        $this->endpoint_factory->update($endpoint, $update);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $datatype->getEndpoint($endpoint->getName())->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Watch.
     */
    public function watchAll(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype): ResponseInterface
    {
        $datatype = $this->mandator_factory->getOne($mandator)->getDataType($datatype);
        $cursor = $this->endpoint_factory->watch($datatype);

        return Helper::watchAll($request, $identity, $this->acl, $cursor);
    }
}
