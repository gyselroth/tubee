<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use Generator;
use MongoDB\BSON\ObjectIdInterface;
use Psr\Http\Message\ServerRequestInterface;
use TaskScheduler\JobInterface;
use Tubee\Log\Factory as LogFactory;
use Tubee\Log\LogInterface;
use Tubee\Process\ProcessInterface;
use Tubee\Resource\AbstractResource;
use Tubee\Resource\AttributeResolver;
use Tubee\ResourceNamespace\ResourceNamespaceInterface;

class Process extends AbstractResource implements ProcessInterface
{
    /**
     * Namespace.
     *
     * @var ResourceNamespace
     */
    protected $namespace;

    /**
     * Log factory.
     *
     * @var LogFactory
     */
    protected $log_factory;

    /**
     * Process.
     */
    public function __construct(array $resource, ResourceNamespaceInterface $namespace, LogFactory $log_factory)
    {
        $this->resource = $resource;
        $this->namespace = $namespace;
        $this->log_factory = $log_factory;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(ServerRequestInterface $request): array
    {
        $result = [
            '_links' => [
                'self' => ['href' => (string) $request->getUri()],
            ],
            'kind' => 'Process',
            'namespace' => $this->namespace->getName(),
            'changed' => $this->getCreated()->toDateTime()->format('c'),
            'data' => $this->getData(),
            'status' => [
                'started' => $this->resource['status'] === 0 ? null : $this->resource['started']->toDateTime()->format('c'),
                'ended' => $this->resource['status'] <= 2 ? null : $this->resource['ended']->toDateTime()->format('c'),
                'result' => JobInterface::STATUS_MAP[$this->resource['status']],
                'code' => $this->resource['status'],
            ],
        ];

        return AttributeResolver::resolve($request, $this, $result);
    }

    /**
     * {@inheritdoc}
     */
    public function getLogs(array $query = [], ?int $offset = null, ?int $limit = null, ?array $sort = []): Generator
    {
        $query['$or'][] = ['context.process' => (string) $this->getId()];
        $query['$or'][] = ['context.parent' => (string) $this->getId()];

        return $this->log_factory->getAll($this->namespace, $query, $offset, $limit, $sort);
    }

    /**
     * {@inheritdoc}
     */
    public function getLog(ObjectIdInterface $id): LogInterface
    {
        return $this->log_factory->getOne($this->namespace, $id);
    }
}
