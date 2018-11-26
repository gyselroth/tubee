<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Storage;

use Icewind\SMB\NativeServer;
use InvalidArgumentException;
use MongoDB\BSON\ObjectId;
use Psr\Log\LoggerInterface;
use Tubee\Storage\Balloon\ApiClient;

class Factory
{
    /**
     * Build instance.
     */
    public static function build(array $resource, LoggerInterface $logger): StorageInterface
    {
        switch ($resource['kind']) {
            case 'Smb':
                $server = new NativeServer($resource['host'], $resource['username'], $resource['password']);

                return new Smb($server, $logger, $resource['share'], $resource['root']);

            break;
            case 'LocalFilesystem':
                return new LocalFilesystem($resource['root'], $logger);

            break;
            case 'Balloon':
                $server = new ApiClient(['host' => $resource['host']], $logger);
                $collection = isset($resource['collection']) ? new ObjectId($resource['collection']) : null;

                return new Balloon($server, $logger, $collection);

            break;
            case 'Stream':
                return new Stream($logger);

            break;
            default:
                throw new InvalidArgumentException('storage class does not exists');
        }
    }
}
