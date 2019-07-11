<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
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
use Tubee\ResourceNamespace\Factory as ResourceNamespaceFactory;
use Tubee\Rest\Helper;
use Tubee\Workflow\Factory as WorkflowFactory;
use Zend\Diactoros\Response;

class Workflows
{
    /**
     * ResourceNamespace factory.
     *
     * @var ResourceNamespaceFactory
     */
    protected $namespace_factory;

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
    public function __construct(ResourceNamespaceFactory $namespace_factory, WorkflowFactory $workflow_factory, Acl $acl)
    {
        $this->namespace_factory = $namespace_factory;
        $this->workflow_factory = $workflow_factory;
        $this->acl = $acl;
    }

    /**
     * Entrypoint.
     */
    public function getAll(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection, string $endpoint): ResponseInterface
    {
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
        ], $request->getQueryParams());

        $namespace = $this->namespace_factory->getOne($namespace);
        $endpoint = $namespace->getCollection($collection)->getEndpoint($endpoint);

        if (isset($query['watch'])) {
            $cursor = $this->workflow_factory->watch($endpoint, null, isset($query['stream']), $query['query'], (int) $query['offset'], (int) $query['limit'], $query['sort']);

            return Helper::watchAll($request, $identity, $this->acl, $cursor);
        }

        $workflows = $endpoint->getWorkflows($query['query'], (int) $query['offset'], (int) $query['limit'], $query['sort']);

        return Helper::getAll($request, $identity, $this->acl, $workflows);
    }

    /**
     * Entrypoint.
     */
    public function getOne(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection, string $endpoint, string $workflow): ResponseInterface
    {
        $namespace = $this->namespace_factory->getOne($namespace);
        $workflow = $namespace->getCollection($collection)->getEndpoint($endpoint)->getWorkflow($workflow);

        return Helper::getOne($request, $identity, $workflow);
    }

    /**
     * Create.
     */
    public function post(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection, string $endpoint): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();

        $namespace = $this->namespace_factory->getOne($namespace);
        $endpoint = $namespace->getCollection($collection)->getEndpoint($endpoint);
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
    public function delete(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection, string $endpoint, string $workflow): ResponseInterface
    {
        $namespace = $this->namespace_factory->getOne($namespace);
        $endpoint = $namespace->getCollection($collection)->getEndpoint($endpoint);
        $this->workflow_factory->deleteOne($endpoint, $workflow);

        return(new Response())->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);
    }

    /**
     * Patch.
     */
    public function patch(ServerRequestInterface $request, Identity $identity, string $namespace, string $collection, string $endpoint, string $workflow): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();
        $namespace = $this->namespace_factory->getOne($namespace);
        $endpoint = $namespace->getCollection($collection)->getEndpoint($endpoint);
        $workflow = $endpoint->getWorkflow($workflow);
        $doc = ['data' => $workflow->getData()];

        $patch = new Patch(json_encode($doc), json_encode($body));
        $patched = $patch->apply();
        $update = json_decode($patched, true);

        $this->workflow_factory->update($workflow, $update);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $endpoint->getWorkflow($workflow->getName())->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }
}
