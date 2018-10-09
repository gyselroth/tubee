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
use Tubee\DataObject\DataObjectInterface;
use Tubee\Resource\AbstractResource;
use Tubee\Resource\AttributeResolver;

class DataObjectRelation extends AbstractResource implements DataObjectRelationInterface
{
    /**
     * Data object.
     *
     * @var DataObjectInterface
     */
    protected $object;

    /**
     * Data object.
     */
    public function __construct(array $resource, DataObjectInterface $object)
    {
        $this->resource = $resource;
        $this->object = $object;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(ServerRequestInterface $request): array
    {
        $datatype = $this->getDataType();
        $mandator = $datatype->getMandator();

        $resource = [
            '_links' => [
                 'self' => ['href' => (string) $request->getUri()],
                 'mandator' => ['href' => ($mandator = (string) $request->getUri()->withPath('/api/v1/mandators/'.$mandator->getName()))],
                 'datatype' => ['href' => $mandator.'/datatypes'.$datatype->getName()],
            ],
            'kind' => 'DataObjectRelation',
            'context' => $this->resource['context'],
        ];

        return AttributeResolver::resolve($request, $this, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function getContext(): array
    {
        return $this->resource['context'];
    }

    /**
     * {@inheritdoc}
     */
    public function getDataObject(): DataObjectInterface
    {
        return $this->object;
    }
}
