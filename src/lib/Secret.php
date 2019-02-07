<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use Psr\Http\Message\ServerRequestInterface;
use Tubee\Resource\AbstractResource;
use Tubee\Resource\AttributeResolver;
use Tubee\ResourceNamespace\ResourceNamespaceInterface;
use Tubee\Secret\SecretInterface;

class Secret extends AbstractResource implements SecretInterface
{
    /**
     * Kind.
     */
    public const KIND = 'Secret';

    /**
     * Namespace.
     *
     * @var ResourceNamespaceInterface
     */
    protected $namespace;

    /**
     * Initialize.
     */
    public function __construct(array $resource, ResourceNamespaceInterface $namespace)
    {
        $this->resource = $resource;
        $this->namespace = $namespace;
    }

    /**
     * Decorate.
     */
    public function decorate(ServerRequestInterface $request): array
    {
        $resource = [
            '_links' => [
                'namespace' => ['href' => (string) $request->getUri()->withPath('/api/v1/namespaces/'.$this->namespace->getName())],
            ],
            'kind' => 'Secret',
            'namespace' => $this->namespace->getName(),
            'data' => $this->getData(),
       ];

        return AttributeResolver::resolve($request, $this, $resource);
    }
}
