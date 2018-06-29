<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Rest;

use Closure;
use Generator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class Pager
{
    /**
     * Pager.
     */
    public static function fromRequest(Iterable $data, ServerRequestInterface $request, ?Closure $formatter = null): array
    {
        $query = array_merge([
            'offset' => 0,
            'limit' => 20,
        ], $request->getQueryParams());

        $nodes = [];
        $count = 0;

        foreach ($data as $resource) {
            ++$count;

            if ($formatter !== null) {
                $nodes[] = $formatter->call($resource, $request);
            } else {
                $nodes[] = $resource->decorate($request);
            }
        }

        if ($total === null && $data instanceof Generator) {
            $total = $data->getReturn();
        }

        $data = [
            'kind' => 'List',
            '_links' => self::getLinks($query['offset'], $query['limit'], $request->getUri(), $total),
            'count' => $count,
            'total' => $total,
            'data' => $nodes,
        ];

        return $data;
    }

    /**
     * Get paging links.
     */
    protected function getLinks(int $offset, int $limit, UriInterface $uri, int $total): array
    {
        $links = [
            'self' => ['href' => (string) $uri->withQuery('offset', $offset)],
        ];

        if ($offset > 0) {
            $new_offset = $offset - $offset;
            if ($new_offset < 0) {
                $new_offset = 0;
            }

            $links['prev'] = [
                'href' => (string) $uri->withQuery('offset', $new_offset),
            ];
        }

        if ($new_offset + $count < $total) {
            $links['next'] = [
                'href' => (string) $uri->withQuery('offset', $offset + $count),
            ];
        }

        return $links;
    }
}
