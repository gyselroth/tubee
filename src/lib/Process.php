<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2021 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use DateTime;
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
     * Kind.
     */
    public const KIND = 'Process';

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
        $data = $this->getData();
        $parent = isset($data['parent']) ? (string) $data['parent'] : null;
        $job = isset($data['job']) ? $data['job'] : null;
        unset($data['parent'], $data['namespace'], $data['job']);
        $estimated = null;

        if ($this->resource['status'] > 1 && isset($this->resource['progress']) && $this->resource['progress'] >= 1) {
            $estimated = new DateTime('@'.(string) round((time() - $this->resource['started']->toDateTime()->format('U')) / $this->resource['progress'] * 100 + time()));
        }

        $result = [
            '_links' => [
                'namespace' => ['href' => (string) $request->getUri()->withPath('/api/v1/namespaces/'.$this->namespace->getName())],
            ],
            'kind' => 'Process',
            'namespace' => $this->namespace->getName(),
            'changed' => $this->getCreated()->toDateTime()->format('c'),
            'data' => $data,
            'status' => [
                'job' => $job,
                'errors' => $this->resource['data']['error_count'] ?? 0,
                'progress' => $this->resource['progress'] ?? 0.0,
                'parent' => $parent,
                'next' => $this->resource['options']['at'] === 0 ? null : (new DateTime('@'.(string) $this->resource['options']['at']))->format('c'),
                'estimated' => $estimated === null ? null : $estimated->format('c'),
                'started' => $this->resource['started'] === null ? null : $this->resource['started']->toDateTime()->format('c'),
                'ended' => $this->resource['ended'] === null ? null : $this->resource['ended']->toDateTime()->format('c'),
                'result' => JobInterface::STATUS_MAP[$this->resource['status']],
                'code' => $this->resource['status'],
            ],
        ];

        return AttributeResolver::resolve($request, $this, $result);
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceNamespace(): ResourceNamespaceInterface
    {
        return $this->namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogs(array $query = [], ?int $offset = null, ?int $limit = null, ?array $sort = []): Generator
    {
        $query['$or'][] = ['context.process' => (string) $this->getId()];
        $query['$or'][] = ['context.parent' => (string) $this->getId()];

        return $this->log_factory->getAll($query, $offset, $limit, $sort);
    }

    /**
     * {@inheritdoc}
     */
    public function getLog(ObjectIdInterface $id): LogInterface
    {
        return $this->log_factory->getOne($id);
    }
}
