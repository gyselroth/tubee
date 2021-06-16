<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2021 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Job;

use Tubee\Resource\ResourceInterface;
use Tubee\ResourceNamespace\ResourceNamespaceInterface;

interface JobInterface extends ResourceInterface
{
    /**
     * Get resource namespace.
     */
    public function getResourceNamespace(): ResourceNamespaceInterface;
}
