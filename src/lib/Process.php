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
use Tubee\Log\Factory as LogFactory;
use Tubee\Log\LogInterface;
use Tubee\Process\ProcessInterface;
use Tubee\Resource\AbstractResource;
use Tubee\Resource\AttributeResolver;

class Process extends AbstractResource implements ProcessInterface
{
    /**
     * Log factory.
     *
     * @var LogFactory
     */
    protected $log_factory;

    /**
     * Process.
     */
    public function __construct(array $resource, LogFactory $log_factory)
    {
        $this->resource = $resource;
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
            'changed' => $this->getCreated()->toDateTime()->format('c'),
            'data' => $this->getData(),
            'status' => [
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
        $query['context.process'] = (string) $this->getId();

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
