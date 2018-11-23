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
use Tubee\AccessRule\AccessRuleInterface;
use Tubee\Resource\AbstractResource;
use Tubee\Resource\AttributeResolver;

class AccessRule extends AbstractResource implements AccessRuleInterface
{
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
    public function decorate(ServerRequestInterface $request): array
    {
        $resource = [
            'kind' => 'AccessRule',
            'data' => $this->getData(),
       ];

        return AttributeResolver::resolve($request, $this, $resource);
    }
}
