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
use Tubee\Resource\AbstractResource;
use Tubee\Resource\AttributeResolver;

class Log extends AbstractResource implements JobInterface
{
    /**
     * Scheduler.
     *
     * @var Scheduler
     */
    protected $scheduler;

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
    public function decorate(ServerRequestInterface $request): array
    {
        $options = $this->resource['options'];
        $scheduler = $this->scheduler;
        $resource = $this->resource;

        $result = [
            '_links' => [
                'self' => ['href' => (string) $request->getUri()],
            ],
            'kind' => 'Job',
            'options' => [
                'at' => $options['at'],
                'interval' => $options['interval'],
                'retry' => $options['retry'],
                'retry_interval' => $options['retry_interval'],
                'timeout' => $options['timeout'],
            ],
            'data' => $this->resource,
            'status' => function () use ($scheduler) {
                /*$cursor = $scheduler->getJobs([
                    'data.job' => $resource['_job']
                ]);*/
            },
        ];

        return AttributeResolver::resolve($request, $this, $result);
    }
}
