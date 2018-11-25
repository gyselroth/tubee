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
        $datatype = $this->endpoint->getDataType()->getName();
        $mandator = $this->endpoint->getDataType()->getMandator()->getName();

        $resource = [
            '_links' => [
                'mandator' => ['href' => (string) $request->getUri()->withPath('/api/v1/mandators/'.$mandator)],
                'datatype' => ['href' => (string) $request->getUri()->withPath('/api/v1/mandators/'.$mandator.'/datatypes/'.$datatype)],
                'endpoint' => ['href' => (string) $request->getUri()->withPath('/api/v1/mandators/'.$mandator.'/datatypes/'.$datatype.'/endpoints/'.$endpoint)],
            ],
            'kind' => 'EndpointObject',
            'mandator' => $mandator,
            'datatype' => $datatype,
            'endpoint' => $endpoint,
            'data' => $this->getData(),
        ];

        return AttributeResolver::resolve($request, $this, $resource);
    }

    /**
     * Get endpoint.
     */
    public function getEndpoint(): EndpointInterface
    {
        return $this->endpoint;
    }
}
