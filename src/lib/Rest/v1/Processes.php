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
use MongoDB\BSON\ObjectId;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tubee\Acl;
use Tubee\Log\Factory as LogFactory;
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
     * Log factory.
     *
     * @var LogFactory
     */
    protected $log_factory;

    /**
     * Init.
     */
    public function __construct(ProcessFactory $process_factory, Acl $acl, ResourceNamespaceFactory $namespace_factory, LogFactory $log_factory)
    {
        $this->process_factory = $process_factory;
        $this->acl = $acl;
        $this->namespace_factory = $namespace_factory;
        $this->log_factory = $log_factory;
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

        if (isset($query['watch'])) {
            $cursor = $this->process_factory->watch($namespace, null, isset($query['stream']), $query['query'], (int) $query['offset'], (int) $query['limit'], $query['sort']);

            return Helper::watchAll($request, $identity, $this->acl, $cursor);
        }

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
        $process = $this->process_factory->getOne($namespace, $id);

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
        $this->process_factory->deleteOne($namespace, $process);

        return (new Response())->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);
    }

    /**
     * Entrypoint.
     */
    public function getAllLogs(ServerRequestInterface $request, Identity $identity, string $namespace, string $process): ResponseInterface
    {
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
        ], $request->getQueryParams());

        $filter = [
            '$or' => [[
                'context.namespace' => $namespace,
                'context.process' => $process,
            ], [
                'context.namespace' => $namespace,
                'context.parent' => $process,
            ]],
        ];

        if (!empty($query['query'])) {
            $filter = ['$and' => [$filter, $query['query']]];
        }

        if (isset($query['watch'])) {
            $logs = $this->log_factory->watch(null, isset($query['stream']), $filter, (int) $query['offset'], (int) $query['limit'], $query['sort']);

            return Helper::watchAll($request, $identity, $this->acl, $logs);
        }

        $logs = $this->log_factory->getAll($filter, (int) $query['offset'], (int) $query['limit'], $query['sort']);

        return Helper::getAll($request, $identity, $this->acl, $logs);
    }

    /**
     * Entrypoint.
     */
    public function getOneLog(ServerRequestInterface $request, Identity $identity, string $namespace, string $process, ObjectId $log): ResponseInterface
    {
        $resource = $this->log_factory->getOne($log);

        return Helper::getOne($request, $identity, $resource);
    }
}
