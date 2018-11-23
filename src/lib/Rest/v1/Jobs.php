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
use Tubee\Job;
use Tubee\Job\Factory as JobFactory;
use Tubee\Mandator\Factory as MandatorFactory;
use Tubee\Rest\Helper;
use Zend\Diactoros\Response;

class Jobs
{
    /**
     * mandator factory.
     *
     * @var MandatorFactory
     */
    protected $mandator_factory;

    /**
     * Job factory.
     *
     * @var JobFactory
     */
    protected $job_factory;

    /**
     * Acl.
     *
     * @var Acl
     */
    protected $acl;

    /**
     * Init.
     */
    public function __construct(JobFactory $job_factory, Acl $acl, MandatorFactory $mandator_factory)
    {
        $this->job_factory = $job_factory;
        $this->acl = $acl;
        $this->mandator_factory = $mandator_factory;
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

        $jobs = $this->job_factory->getAll($query['query'], $query['offset'], $query['limit'], $query['sort']);

        return Helper::getAll($request, $identity, $this->acl, $jobs);
    }

    /**
     * Entrypoint.
     */
    public function getOne(ServerRequestInterface $request, Identity $identity, string $job): ResponseInterface
    {
        $resource = $this->job_factory->getOne($job);

        return Helper::getOne($request, $identity, $resource);
    }

    /**
     * Delete job.
     */
    public function delete(ServerRequestInterface $request, Identity $identity, string $job): ResponseInterface
    {
        $this->job_factory->deleteOne($job);

        return (new Response())->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);
    }

    /**
     * Add new job.
     */
    public function post(ServerRequestInterface $request, Identity $identity): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();
        $job = array_merge(['mandators' => []], $body);

        $this->mandator_factory->getAll($job['mandators']);
        $id = $this->job_factory->create($job);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_ACCEPTED),
            $this->job_factory->getOne($job['name'])->decorate($request),
            ['pretty' => isset($query['pretty'])]
        );
    }

    /**
     * Patch.
     */
    public function patch(ServerRequestInterface $request, Identity $identity, string $job): ResponseInterface
    {
        $body = $request->getParsedBody();
        $query = $request->getQueryParams();
        $job = $this->job_factory->getOne($job);
        $doc = ['data' => $job->getData()];

        $patch = new Patch(json_encode($doc), json_encode($body));
        $patched = $patch->apply();
        $update = json_decode($patched, true);
        $this->job_factory->update($job, $update);

        return new UnformattedResponse(
            (new Response())->withStatus(StatusCodeInterface::STATUS_OK),
            $this->job_factory->getOne($job->getName())->decorate($request),
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

        $cursor = $this->job_factory->watch(null, true, $query['query'], $query['offset'], $query['limit'], $query['sort']);

        return Helper::watchAll($request, $identity, $this->acl, $cursor);
    }
}
