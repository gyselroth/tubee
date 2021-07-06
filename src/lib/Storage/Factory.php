<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2021 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Storage;

use Icewind\SMB\AnonymousAuth;
use Icewind\SMB\BasicAuth;
use Icewind\SMB\ServerFactory;
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
            case 'SmbStorage':
                $factory = new ServerFactory();

                if ($resource['username'] && $resource['password']) {
                    $auth = new BasicAuth($resource['username'], $resource['workgroup'], $resource['password']);
                } else {
                    $auth = new AnonymousAuth();
                }

                $server = $factory->createServer($resource['host'], $auth);

                return new Smb($server, $logger, $resource['share'], $resource['root']);

            break;
            case 'LocalFilesystemStorage':
                return new LocalFilesystem($resource['root'], $logger);

            break;
            case 'BalloonStorage':
                unset($resource['kind']);
                $collection = isset($resource['collection']) ? new ObjectId($resource['collection']) : null;
                unset($resource['collection']);
                $server = new ApiClient($resource, $logger);

                return new Balloon($server, $logger, $collection);

            break;
            case 'StreamStorage':
                return new Stream($logger);

            break;
            default:
                throw new InvalidArgumentException('storage class does not exists');
        }
    }
}
