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
use Tubee\MandatorManager;
use Tubee\Rest\Pager;
use Zend\Diactoros\Response;

class ObjectEndpoints
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
    public function getAll(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype, ObjectId $object): ResponseInterface
    {
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
            'query' => [],
        ], $request->getQueryParams());

        $mandator = $this->manager->getMandator($mandator);
        $endpoint = $mandator->getDataType($datatype)->getEndpoint($endpoint);

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
    public function getOne(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype, ObjectId $object, string $endpoint): ResponseInterface
    {
        $query = $request->getQueryParams();

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $this->manager->getMandator($mandator)->getDataType($datatype)->getOne(['_id' => $object], false)->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }
}
