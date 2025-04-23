<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use MongoDB\BSON\ObjectId;
use Psr\Http\Message\ServerRequestInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\EndpointObject\EndpointObjectInterface;
use Tubee\Resource\AbstractResource;
use Tubee\Resource\AttributeResolver;

class EndpointObject extends AbstractResource implements EndpointObjectInterface
{
    /**
     * Endpoint.
     *
     * @var EndpointInterface
     */
    protected $endpoint;

    /**
     * Data object.
     */
    public function __construct(array $resource, EndpointInterface $endpoint)
    {
        $resource['_id'] = new ObjectId();
        $this->resource = $resource;
        $this->endpoint = $endpoint;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(ServerRequestInterface $request): array
    {
        $endpoint = $this->endpoint->getName();
        $collection = $this->endpoint->getCollection()->getName();
        $namespace = $this->endpoint->getCollection()->getResourceNamespace()->getName();

        $resource = [
            '_links' => [
                'namespace' => ['href' => (string) $request->getUri()->withPath('/api/v1/namespaces/'.$namespace)],
                'collection' => ['href' => (string) $request->getUri()->withPath('/api/v1/namespaces/'.$namespace.'/collections/'.$collection)],
                'endpoint' => ['href' => (string) $request->getUri()->withPath('/api/v1/namespaces/'.$namespace.'/collections/'.$collection.'/endpoints/'.$endpoint)],
            ],
            'kind' => 'EndpointObject',
            'namespace' => $namespace,
            'collection' => $collection,
            'endpoint' => $endpoint,
            'data' => $this->getData(),
        ];

        return AttributeResolver::resolve($request, $this, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilter()
    {
        return $this->resource['filter'] ?? null;
    }

    /**
     * Get endpoint.
     */
    public function getEndpoint(): EndpointInterface
    {
        return $this->endpoint;
    }
}
