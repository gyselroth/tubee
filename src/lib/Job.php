<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use MongoDB\BSON\ObjectId;
use Psr\Http\Message\ServerRequestInterface;
use Tubee\Job\JobInterface;
use Tubee\Resource\AttributeResolver;

class Job implements JobInterface
{
    /**
     * Job.
     *
     * @var array
     */
    protected $resource;

    /**
     * Data object.
     */
    public function __construct(array $resource)
    {
        $this->resource = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): ObjectId
    {
        return $this->resource['_id'];
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return $this->resource;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(ServerRequestInterface $request): array
    {
        $job = array_intersect_key($this->resource, array_flip(['at', 'interval', 'retry', 'retry_interval', 'created', 'status', 'resource', 'class', 'data']));

        $resource = [
            '_links' => [
                'self' => ['href' => (string) $request->getUri()],
            ],
            'kind' => 'Job',
            'id' => (string) $this->getId(),
        ];

        if (isset($job['at'])) {
            $job['at'] = $job['at']->toDateTime()->format('c');
        }

        $resource = array_merge($resource, $job);

        return AttributeResolver::resolve($request, $this, $resource);
    }
}
