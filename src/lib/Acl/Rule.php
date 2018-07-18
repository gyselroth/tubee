<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Acl;

use MongoDB\BSON\ObjectId;
use Psr\Http\Message\ServerRequestInterface;
use Tubee\Acl\Rule\RuleInterface;
use Tubee\Resource\AttributeResolver;

class Rule implements RuleInterface
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
        $result = $this->data;
        unset($result['_id']);

        $resource = [
            '_links' => [
                'self' => ['href' => (string) $request->getUri()],
            ],
            'kind' => 'AccessRule',
            'id' => (string) $this->getId(),
        ] + $result;

        return AttributeResolver::resolve($request, $this, $resource);
    }
}
