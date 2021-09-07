<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2021 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee;

use Psr\Http\Message\ServerRequestInterface;
use Tubee\DataObject\DataObjectInterface;
use Tubee\DataObjectRelation\DataObjectRelationInterface;
use Tubee\Resource\AbstractResource;
use Tubee\Resource\AttributeResolver;

class DataObjectRelation extends AbstractResource implements DataObjectRelationInterface
{
    /**
     * Kind.
     */
    public const KIND = 'DataObjectRelation';

    /**
     * Data object.
     *
     * @var DataObjectInterface
     */
    protected $object;

    /**
     * Data object.
     */
    public function __construct(array $resource, ?DataObjectInterface $object = null)
    {
        $this->resource = $resource;
        $this->object = $object;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(ServerRequestInterface $request): array
    {
        $resource = [
            '_links' => [
                 'self' => ['href' => (string) $request->getUri()],
            ],
            'kind' => 'DataObjectRelation',
            'namespace' => $this->resource['namespace'],
            'data' => $this->getData(),
        ];

        if ($this->object !== null) {
            $resource['status']['object'] = $this->object->decorate($request);
        }

        return AttributeResolver::resolve($request, $this, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function getContext(): array
    {
        return $this->resource['data']['context'];
    }

    /**
     * {@inheritdoc}
     */
    public function getDataObject(): ?DataObjectInterface
    {
        return $this->object;
    }

    public function getDataObjectByRelation(DataObjectRelationInterface $relation, Collection $collection): ?DataObjectInterface
    {
        $relationData = $relation->getData();
        if (isset($relationData['relation'])) {
            foreach ($relationData['relation'] as $object) {
                if (isset($object['collection']) && $object['collection'] === $collection->getName()) {
                    return $collection->getObject(['name' => $object['object']]);
                }
            }
        }

        return null;
    }
}
