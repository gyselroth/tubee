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
use Tubee\Rest\Pager;
use Tubee\Workflow\Factory as WorkflowFactory;
use Zend\Diactoros\Response;

class Workflows
{
    /**
     * Init.
     */
    public function __construct(MandatorFactory $mandator, WorkflowFactory $workflow, Acl $acl)
    {
        $this->mandator = $mandator;
        $this->workflow = $workflow;
        $this->acl = $acl;
    }

    /**
     * Entrypoint.
     */
    public function getAll(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype, string $endpoint): ResponseInterface
    {
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
            'query' => [],
        ], $request->getQueryParams());

        $mandator = $this->mandator->getOne($mandator);
        $workflows = $mandator->getDataType($datatype)->getEndpoint($endpoint)->getWorkflows($query['query'], (int) $query['offset'], (int) $query['limit']);

        $body = $this->acl->filterOutput($request, $identity, $workflows);
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
    public function getOne(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype, string $endpoint, string $workflow): ResponseInterface
    {
        $query = $request->getQueryParams();

        $mandator = $this->mandator->getOne($mandator);
        $workflow = $mandator->getDataType($datatype)->getEndpoint($endpoint)->getWorkflow($workflow);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $endpoint->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Create.
     */
    public function post(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype, string $endpoint): ResponseInterface
    {
        $body = $request->getParsedBody();

        $mandator = $this->mandator->getOne($mandator);
        $endpoint = $mandator->getDataType($datatype)->getEndpoint($endpoint);
        $this->workflow->add($endpoint, $body);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_CREATED),
            $endpoint->getWorkflow($body['name'])->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Delete.
     */
    public function delete(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype, string $endpoint, string $workflow): ResponseInterface
    {
        $mandator = $this->mandator->getOne($mandator);
        $endpoint = $mandator->getDataType($datatype)->getEndpoint($endpoint);
        $this->workflow->delete($endpoint, $workflow);

        return(new Response())->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);
    }
}
