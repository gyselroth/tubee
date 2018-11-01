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
use Tubee\Mandator\Factory as MandatorFactory;
use Tubee\Rest\Helper;
use Zend\Diactoros\Response;

class Mandators
{
    /**
     * Mandator factory.
     *
     * @var MandatorFactory
     */
    protected $mandator_factory;

    /**
     * Acl.
     *
     * @var Acl
     */
    protected $acl;

    /**
     * Init.
     */
    public function __construct(MandatorFactory $mandator, Acl $acl)
    {
        $this->mandator_factory = $mandator;
        $this->acl = $acl;
    }

    /**
     * Entrypoint.
     */
    public function getAll(ServerRequestInterface $request, Identity $identity): ResponseInterface
    {
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
        ], $request->getQueryParams());

        $mandators = $this->mandator_factory->getAll($query['query'], (int) $query['offset'], (int) $query['limit'], $query['sort']);

        return Helper::getAll($request, $identity, $this->acl, $mandators);
    }

    /**
     * Entrypoint.
     */
    public function getOne(ServerRequestInterface $request, Identity $identity, string $mandator): ResponseInterface
    {
        $resource = $this->mandator_factory->getOne($mandator);

        return Helper::getOne($request, $identity, $resource);
    }

    /**
     * Create.
     */
    public function post(ServerRequestInterface $request, Identity $identity): ResponseInterface
    {
        $body = $request->getParsedBody();
        $id = $this->mandator_factory->add($body);
        $query = $request->getQueryParams();

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_CREATED),
            $this->mandator_factory->getOne($body['name'])->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Watch.
     */
    public function watchAll(ServerRequestInterface $request, Identity $identity): ResponseInterface
    {
        $query = array_merge([
            'offset' => null,
            'limit' => null,
            'existing' => true,
        ], $request->getQueryParams());

        $cursor = $this->mandator_factory->watch($query['query'], $query['offset'], $query['limit'], $query['sort']);

        return Helper::watchAll($request, $identity, $this->acl, $cursor);
    }
}
