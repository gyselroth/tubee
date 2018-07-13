<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Job;

use MongoDB\BSON\ObjectId;
use Psr\Http\Message\ServerRequestInterface;
use Tubee\Job\Job\JobInterface;

class Job implements JobInterface
{
    /**
     * Object id.
     *
     * @var ObjectId
     */
    protected $_id;

    /**
     * Job.
     *
     * @var array
     */
    protected $data;

    /**
     * Data object.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): ObjectId
    {
        return $this->data['_id'];
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return [
            '_id' => $this->_id,
            'created' => $this->created,
            'changed' => $this->changed,
            'deleted' => $this->deleted,
            'version' => $this->version,
            'data' => $this->data,
            'endpoints' => $this->endpoints,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(ServerRequestInterface $request): array
    {
        $job = array_intersect_key($this->data, array_flip(['at', 'interval', 'retry', 'retry_interval', 'created', 'status', 'data', 'class']));

        $data = [
            '_links' => [
                'self' => ['href' => (string) $request->getUri()],
            ],
            'kind' => 'Job',
            'id' => (string) $this->getId(),
        ];

        if (isset($job['created'])) {
            $job['created'] = $job['created']->toDateTime()->format('c');
        }

        if (isset($job['at'])) {
            $job['at'] = $job['at']->toDateTime()->format('c');
        }

        return array_merge($data, $job);
    }
}
