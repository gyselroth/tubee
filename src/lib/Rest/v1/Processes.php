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
use Tubee\Process\Factory as ProcessFactory;
use Tubee\Rest\Helper;
use Zend\Diactoros\Response;

class Processes
{
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
    public function __construct(Acl $acl, ProcessFactory $process_factory)
    {
        $this->acl = $acl;
        $this->process_factory = $process_factory;
    }

    /**
     * Entrypoint.
     */
    public function getAll(ServerRequestInterface $request, Identity $identity, ?string $job = null): ResponseInterface
    {
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
        ], $request->getQueryParams());

        $processes = $this->process_factory->getAll($query['query'], $query['offset'], $query['limit'], $query['sort']);

        return Helper::getAll($request, $identity, $this->acl, $processes);
    }

    /**
     * Entrypoint.
     */
    public function getOne(ServerRequestInterface $request, Identity $identity, ObjectIdInterface $process): ResponseInterface
    {
        $resource = $this->process_factory->getOne($process);

        return Helper::getOne($request, $identity, $resource);
    }

    /**
     * Force trigger job.
     */
    public function post(ServerRequestInterface $request, Identity $identity): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();
        $id = $this->process_factory->create($body);
        $process = $this->process_factory->getOne($id);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_CREATED),
            $process->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Stop process.
     */
    public function delete(ServerRequestInterface $request, Identity $identity, ObjectId $process): ResponseInterface
    {
        $this->process_factory->getOne($process)->deleteOne($process);

        return (new Response())->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);
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

        $cursor = $this->process_factory->watch(null, true, $query['query'], $query['offset'], $query['limit'], $query['sort']);

        return Helper::watchAll($request, $identity, $this->acl, $cursor);
    }
}
