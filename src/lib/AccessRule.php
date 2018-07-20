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
use Tubee\AccessRule\AccessRuleInterface;
use Tubee\Resource\AttributeResolver;

class AccessRule implements AccessRuleInterface
{
    /**
     * Resource.
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
    public function getName(): string
    {
        return $this->resource['name'];
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
        $result = $this->resource;
        unset($result['_id']);

        $resource = [
            '_links' => [
                'self' => ['href' => (string) $request->getUri()],
            ],
            'kind' => 'AccessRule',
        ] + $result;

        return AttributeResolver::resolve($request, $this, $resource);
    }
}
