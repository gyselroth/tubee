<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Unit\Storage;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tubee\Endpoint\Balloon\ApiClient;
use Tubee\Storage\Balloon;
use Tubee\Storage\Exception;

class BalloonTest extends TestCase
{
    public function setUp()
    {
        file_put_contents(__DIR__.'/Mock/bar.csv', 'bar;bar');
        file_put_contents(__DIR__.'/Mock/foo.csv', 'foo;foo');
    }

    public function testGetFiles()
    {
        $api = $this->createMock(ApiClient::class);
        $api->method('restCall')->willReturn([
            'data' => [[
                'id' => '1',
                'name' => 'bar.csv',
            ], [
                'id' => '2',
                'name' => 'foo.csv',
            ]],
        ]);

        $storage = new Balloon($api, $this->createMock(LoggerInterface::class));

        $files = $storage->getFiles('\.csv$');
        $this->assertSame('bar.csv', $files[0]['name']);
        $this->assertSame('bar.csv', $files[0]['name']);
    }

    public function testOpenReadStreams()
    {
        $api = $this->createMock(ApiClient::class);
        $api->method('restCall')->willReturn([
            'data' => [[
                'id' => '1',
                'name' => 'bar.csv',
            ], [
                'id' => '2',
                'name' => 'foo.csv',
            ]],
        ]);

        $api->method('openSocket')->will($this->returnCallback(function ($uri) {
            if ($uri === '/api/v2/nodes/1/content') {
                return fopen(__DIR__.'/Mock/bar.csv', 'r+');
            }

            return fopen(__DIR__.'/Mock/foo.csv', 'r+');
        }));

        $storage = new Balloon($api, $this->createMock(LoggerInterface::class));
        $streams = $storage->openReadStreams('\.csv$');
        $streams = iterator_to_array($streams);
        $this->assertSame('bar;bar', fread($streams['bar.csv'], 100));
        $this->assertSame('foo;foo', fread($streams['foo.csv'], 100));
    }

    public function testOpenReadStream()
    {
        $api = $this->createMock(ApiClient::class);
        $api->method('restCall')->willReturn([
            'data' => [[
                'id' => '1',
                'name' => 'bar.csv',
            ]],
        ]);

        $api->method('openSocket')->willReturn(fopen(__DIR__.'/Mock/bar.csv', 'r+'));
        $storage = new Balloon($api, $this->createMock(LoggerInterface::class));
        $stream = $storage->openReadStream('bar.csv');
        $this->assertSame('bar;bar', fread($stream, 100));
    }

    public function testOpenReadStreamFailed()
    {
        $this->expectException(Exception\OpenStreamFailed::class);
        $api = $this->createMock(ApiClient::class);
        $api->method('openSocket')->willReturn(false);
        $storage = new Balloon($api, $this->createMock(LoggerInterface::class));
        $stream = @$storage->openReadStream('bar');
    }

    public function testOpenWriteStream()
    {
        $storage = new Balloon($this->createMock(ApiClient::class), $this->createMock(LoggerInterface::class));
        $stream = $storage->openWriteStream('bar.csv');
        $this->assertSame(7, fwrite($stream, 'foo;foo'));
    }

    public function testSyncWriteStreamNullBytes()
    {
        $api = $this->createMock(ApiClient::class);
        $api->expects($this->exactly(0))->method('restCall');
        $storage = new Balloon($api, $this->createMock(LoggerInterface::class));
        $stream = fopen('php://temp', 'r+');
        $storage->syncWriteStream($stream, 'foo');
    }

    public function testSyncWriteStreamOneChunk()
    {
        $api = $this->createMock(ApiClient::class);
        $api->expects($this->exactly(1))->method('restCall');
        $storage = new Balloon($api, $this->createMock(LoggerInterface::class));
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, random_bytes(10));
        $storage->syncWriteStream($stream, 'foo');
    }

    public function testSyncWriteStreamTwoChunks()
    {
        $api = $this->createMock(ApiClient::class);
        $api->expects($this->exactly(2))->method('restCall');
        $storage = new Balloon($api, $this->createMock(LoggerInterface::class));
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, random_bytes(9000000));
        $storage->syncWriteStream($stream, 'foo');
    }
}
