<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2021 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Collection\Exception;

use Tubee\Rest\Exception\ExceptionInterface;

class NotFound extends \Tubee\Exception implements ExceptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function getStatusCode(): int
    {
        return 404;
    }
}
