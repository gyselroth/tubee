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
use Tubee\Process\Factory as ProcessFactory;
use Tubee\ResourceNamespace\Factory as ResourceNamespaceFactory;
use Tubee\Rest\Helper;
use Zend\Diactoros\Response;

class Processes
{
    /**
     * Namespace factory.
     *
     * @var ResourceNamespaceFactory
     */
    protected $namespace_factory;

    /**
     * Process factory.
     *
     * @var ProcessFactory
     */
    protected $process_factory;

    /**
     * Acl.
     *
     * @var Acl
     */
    protected $acl;

    /**
     * Init.
     */
    public function __construct(Acl $acl, ProcessFactory $process_factory, ResourceNamespaceFactory $namespace_factory)
    {
        $this->acl = $acl;
        $this->process_factory = $process_factory;
        $this->namespace_factory = $namespace_factory;
    }

    /**
     * Entrypoint.
     */
    public function getAll(ServerRequestInterface $request, Identity $identity, string $namespace): ResponseInterface
    {
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
        ], $request->getQueryParams());

        $namespace = $this->namespace_factory->getOne($namespace);
        $processes = $this->process_factory->getAll($namespace, $query['query'], $query['offset'], $query['limit'], $query['sort']);

        return Helper::getAll($request, $identity, $this->acl, $processes);
    }

    /**
     * Entrypoint.
     */
    public function getOne(ServerRequestInterface $request, Identity $identity, string $namespace, ObjectId $process): ResponseInterface
    {
        $namespace = $this->namespace_factory->getOne($namespace);
        $resource = $this->process_factory->getOne($namespace, $process);

        return Helper::getOne($request, $identity, $resource);
    }

    /**
     * Force trigger process.
     */
    public function post(ServerRequestInterface $request, Identity $identity, string $namespace): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();

        $namespace = $this->namespace_factory->getOne($namespace);
        $id = $this->process_factory->create($namespace, $body);
        $process = $this->process_factory->getOne($id);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_ACCEPTED),
            $process->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Stop process.
     */
    public function delete(ServerRequestInterface $request, Identity $identity, string $namespace, ObjectId $process): ResponseInterface
    {
        $namespace = $this->namespace_factory->getOne($namespace);
        $this->process_factory->getOne($process)->deleteOne($namespace, $process);

        return (new Response())->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);
    }

    /**
     * Watch.
     */
    public function watchAll(ServerRequestInterface $request, Identity $identity, string $namespace): ResponseInterface
    {
        $query = array_merge([
            'offset' => null,
            'limit' => null,
            'existing' => true,
        ], $request->getQueryParams());

        $namespace = $this->namespace_factory->getOne($namespace);
        $cursor = $this->process_factory->watch($namespace, null, true, $query['query'], $query['offset'], $query['limit'], $query['sort']);

        return Helper::watchAll($request, $identity, $this->acl, $cursor);
    }
}
