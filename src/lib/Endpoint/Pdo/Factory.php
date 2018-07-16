<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint\Pdo;

use PDO as CorePDO;
use Psr\Log\LoggerInterface;
use Tubee\DataType\DataTypeInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Endpoint\Pdo as PdoEndpoint;

class Factory
{
    /**
     * Build instance.
     */
    public static function build(array $resource, DataTypeInterface $datatype, LoggerInterface $logger): EndpointInterface
    {
        $options = array_values(array_merge([
            'dsn' => null,
            'username' => null,
            'passwd' => null,
            'options' => null,
        ], $resource['pdo_options']));

        $pdo = new CorePDO(...$options);
        $wrapper = new Wrapper($pdo, $logger);

        return new PdoEndpoint($resource, $wrapper, $datatype, $logger);
    }
}
