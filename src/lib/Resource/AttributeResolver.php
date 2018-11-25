<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Resource;

use Closure;
use Psr\Http\Message\ServerRequestInterface;
use Tubee\Secret\Factory as SecretFactory;

class AttributeResolver
{
    /**
     * Resolve.
     */
    public static function resolve(ServerRequestInterface $request, ResourceInterface $resource, array $resolvable): array
    {
        $resolvable = array_replace_recursive(self::addResourceMetaData($request, $resource), $resolvable);

        $params = $request->getQueryParams();
        $attributes = [];

        if (isset($params['attributes'])) {
            $attributes = $params['attributes'];
        }

        if (0 === count($attributes)) {
            return SecretFactory::reverse($resource, self::translateAttributes($resolvable, $resource));
        }

        return SecretFactory::reverse($resource, self::translateAttributes($resolvable, array_intersect_key($resolvable, array_flip($attributes))));
    }

    /**
     * Add metadata.
     */
    protected static function addResourceMetadata(ServerRequestInterface $request, ResourceInterface $resource): array
    {
        $data = $resource->toArray();

        return [
            '_links' => [
                'self' => ['href' => (string) $request->getUri()],
            ],
            'id' => (string) $resource->getId(),
            'name' => $resource->getName(),
            'version' => isset($data['version']) ? $data['version'] : 0,
            'created' => isset($data['created']) ? $data['created']->toDateTime()->format('c') : null,
            'changed' => isset($data['changed']) ? $data['changed']->toDateTime()->format('c') : null,
            'description' => isset($data['description']) ? $data['description'] : null,
            'secrets' => $resource->getSecrets(),
        ];
    }

    /**
     * Execute closures.
     */
    protected static function translateAttributes(array $resolvable, ResourceInterface $resource): array
    {
        foreach ($resolvable as $key => &$value) {
            if ($value instanceof Closure) {
                $result = $value($resource);
                if (null === $result) {
                    unset($resolvable[$key]);
                } else {
                    $value = $result;
                }
            } elseif ($value === null) {
                unset($resolvable[$key]);
            }
        }

        return $resolvable;
    }
}
