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
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(ServerRequestInterface $request): array
    {
        $data = $this->data;

        return AttributeResolver::resolve($request, $this, [
            '_links' => [
                'self' => ['href' => (string) $request->getUri()],
            ],
            'kind' => 'JobError',
            'id' => (string) $this->getId(),
            'message' => $this->data['message'],
            'created' => (new DateTime($this->data['datetime']))->format('c'),
            'category' => $this->data['context']['category'],
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
