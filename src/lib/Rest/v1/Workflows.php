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
use Tubee\Mandator\Factory as MandatorFactory;
use Tubee\Rest\Helper;
use Tubee\Workflow\Factory as WorkflowFactory;
use Zend\Diactoros\Response;

class Workflows
{
    /**
     * Mandator factory.
     *
     * @var MandatorFactory
     */
    protected $mandator_factory;

    /**
     * Workflow factory.
     *
     * @var WorkflowFactory
     */
    protected $workflow_factory;

    /**
     * Acl.
     *
     * @var Acl
     */
    protected $acl;

    /**
     * Init.
     */
    public function __construct(MandatorFactory $mandator_factory, WorkflowFactory $workflow_factory, Acl $acl)
    {
        $this->mandator_factory = $mandator_factory;
        $this->workflow_factory = $workflow_factory;
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
        ], $request->getQueryParams());

        $mandator = $this->mandator_factory->getOne($mandator);
        $workflows = $mandator->getDataType($datatype)->getEndpoint($endpoint)->getWorkflows($query['query'], (int) $query['offset'], (int) $query['limit'], $query['sort']);

        return Helper::getAll($request, $identity, $this->acl, $workflows);
    }

    /**
     * Entrypoint.
     */
    public function getOne(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype, string $endpoint, string $workflow): ResponseInterface
    {
        $mandator = $this->mandator_factory->getOne($mandator);
        $workflow = $mandator->getDataType($datatype)->getEndpoint($endpoint)->getWorkflow($workflow);

        return Helper::getOne($request, $identity, $workflow);
    }

    /**
     * Create.
     */
    public function post(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype, string $endpoint): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();

        $mandator = $this->mandator_factory->getOne($mandator);
        $endpoint = $mandator->getDataType($datatype)->getEndpoint($endpoint);
        $this->workflow_factory->add($endpoint, $body);

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
        $mandator = $this->mandator_factory->getOne($mandator);
        $endpoint = $mandator->getDataType($datatype)->getEndpoint($endpoint);
        $this->workflow_factory->deleteOne($endpoint, $workflow);

        return(new Response())->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);
    }

    /**
     * Patch.
     */
    public function patch(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype, string $endpoint, string $workflow): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();
        $mandator = $this->mandator_factory->getOne($mandator);
        $endpoint = $mandator->getDataType($datatype)->getEndpoint($endpoint);
        $workflow = $endpoint->getWorkflow($workflow);
        $doc = ['data' => $workflow->getData()];

        $patch = new Patch(json_encode($doc), json_encode($body));
        $patched = $patch->apply();
        $update = json_decode($patched, true);

        $this->workflow_factory->update($workflow, $update);
        exit();

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $endpoint->getWorkflow($workflow->getName())->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Watch.
     */
    public function watchAll(ServerRequestInterface $request, Identity $identity, string $mandator, string $datatype, string $endpoint): ResponseInterface
    {
        $query = array_merge([
            'offset' => null,
            'limit' => null,
            'existing' => true,
        ], $request->getQueryParams());

        $endpoint = $this->mandator_factory->getOne($mandator)->getDataType($datatype)->getEndpoint($endpoint);
        $cursor = $this->workflow_factory->watch($endpoint, null, true, $query['query'], $query['offset'], $query['limit'], $query['sort']);

        return Helper::watchAll($request, $identity, $this->acl, $cursor);
    }
}
