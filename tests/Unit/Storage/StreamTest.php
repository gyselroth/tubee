<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Unit\Storage;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tubee\Storage\Exception;
use Tubee\Storage\Stream;

class StreamTest extends TestCase
{
    protected $storage;

    public function setUp()
    {
        file_put_contents(__DIR__.'/Mock/bar.csv', 'bar;bar');
        $this->storage = new Stream($this->createMock(LoggerInterface::class));
    }

    public function testOpenReadStreams()
    {
        $streams = $this->storage->openReadStreams(__DIR__.'/Mock/bar.csv');
        $streams = iterator_to_array($streams);
        $this->assertSame('bar;bar', fread($streams[__DIR__.'/Mock/bar.csv'], 100));
    }

    public function testOpenReadStream()
    {
        $stream = $this->storage->openReadStream(__DIR__.'/Mock/bar.csv');
        $this->assertSame('bar;bar', fread($stream, 100));
    }

    public function testOpenReadStreamFailed()
    {
        $path = '/tmp/'.uniqid();
        mkdir($path);
        $this->expectException(Exception\OpenStreamFailed::class);
        $this->storage = new Stream($this->createMock(LoggerInterface::class));
        rmdir($path);
        mkdir($path, 0330);
        $stream = @$this->storage->openReadStream('bar');
    }

    public function testOpenWriteStream()
    {
        $stream = $this->storage->openWriteStream('/tmp/bar.csv');
        $this->assertSame(7, fwrite($stream, 'foo;foo'));
    }

    public function testOpenWriteStreamFailed()
    {
        $path = '/tmp/'.uniqid();
        mkdir($path, 0550);
        $this->expectException(Exception\OpenStreamFailed::class);
        $this->storage = new Stream($this->createMock(LoggerInterface::class));
        $stream = @$this->storage->openWriteStream('foo://bar');
    }

    public function testSyncWriteStream()
    {
        $this->assertTrue($this->storage->syncWriteStream('foo', 'bar'));
    }
}
