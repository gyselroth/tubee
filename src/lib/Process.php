<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use Psr\Http\Message\ServerRequestInterface;
use Tubee\Job\JobInterface;
use Tubee\Process\ProcessInterface;
use Tubee\Resource\AbstractResource;
use Tubee\Resource\AttributeResolver;

class Process extends AbstractResource implements ProcessInterface
{
    /**
     * Job.
     *
     * @var JobInterface
     */
    protected $job;

    /**
     * Process.
     */
    public function __construct(array $resource, JobInterface $job)
    {
        $this->resource = $resource;
        $this->job = $job;
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
            'status' => [
                'code' => $this->resource['status'],
            ],
        ];

        return AttributeResolver::resolve($request, $this, $result);
    }
}
