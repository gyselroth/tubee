<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Job;

use DateTime;
use MongoDB\BSON\ObjectId;
use Psr\Http\Message\ServerRequestInterface;
use Tubee\Job\Error\ErrorInterface;
use Tubee\Resource\AttributeResolver;

class Error implements ErrorInterface
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
        $data = $this->resource;

        return AttributeResolver::resolve($request, $this, [
            '_links' => [
                'self' => ['href' => (string) $request->getUri()],
            ],
            'kind' => 'JobError',
            'id' => (string) $this->getId(),
            'level' => $this->resource['level'],
            'level_name' => $this->resource['level_name'],
            'message' => $this->resource['message'],
            'created' => (new DateTime($this->resource['datetime']))->format('c'),
            'category' => $this->resource['context']['category'],
            'exception' => function ($resource) use ($data) {
                if (isset($data['context']['exception'])) {
                    return $data['context']['exception'];
                }
            },
            'object' => function ($resource) use ($data) {
                if (isset($data['context']['object'])) {
                    return $data['context']['object'];
                }
            },
        ]);
    }
}
