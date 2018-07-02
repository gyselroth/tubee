<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\DataObject;

use Closure;
use Psr\Http\Message\ServerRequestInterface;

class AttributeDecorator
{
    /**
     * Decorate attributes.
     */
    public static function decorate(DataObjectInterface $object, ServerRequestInterface $request): array
    {
        $query = $request->getQueryParams();
        $attributes = isset($query['attributes']) ? (array) $query['attributes'] : [];

        $attrs = self::getAttributes($object, $request);
        if (0 === count($attributes)) {
            return self::translateAttributes($object, $attrs);
        }

        return self::translateAttributes($object, array_intersect_key($attrs, array_flip($attributes)));
    }

    /**
     * Get Attributes.
     */
    protected static function getAttributes(DataObjectInterface $object, ServerRequestInterface $request): array
    {
        $data = [
            '_links' => [
                 'self' => ['href' => (string) $request->getUri()],
            ],
            'kind' => 'DataObject',
            'id' => (string) $object->getId(),
            'mandator' => function ($object) use ($request) {
                return $object->getDataType()->getMandator()->decorate($request);
            },
            'datatype' => function ($object) use ($request) {
                return $object->getDataType()->decorate($request);
            },
            'version' => $object->getVersion(),
            'created' => function ($object) {
                return $object->getCreated()->toDateTime()->format('c');
            },
            'changed' => function ($object) {
                if ($object->getChanged() === null) {
                    return null;
                }

                return $object->getChanged()->toDateTime()->format('c');
            },
            'data' => $object->getData(),
            'endpoints' => function ($object) {
                $endpoints = $object->getEndpoints();
                foreach ($endpoints as &$endpoint) {
                    //$endpoint['last_sync'] = $endpoint['last_sync']->toDateTime()->format('c');
                }

                return $endpoints;
            },
        ];
    }

    /**
     * Execute closures.
     *
     * @param RoleInterface
     */
    protected static function translateAttributes(DataObjectInterface $object, array $attributes): array
    {
        foreach ($attributes as $key => &$value) {
            if ($value instanceof Closure) {
                $result = $value($object);

                if (null === $result) {
                    unset($attributes[$key]);
                } else {
                    $value = $result;
                }
            } elseif ($value === null) {
                unset($attributes[$key]);
            }
        }

        return $attributes;
    }
}
