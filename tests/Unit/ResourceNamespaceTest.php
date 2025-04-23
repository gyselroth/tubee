<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2025 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Unit;

use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Collection\Factory as CollectionFactory;
use Tubee\ResourceNamespace;
use Tubee\ResourceNamespace\Factory as ResourceNamespaceFactory;

class ResourceNamespaceTest extends TestCase
{
    protected $namespace;

    public function setUp()
    {
        $collection = $this->createMock(CollectionFactory::class);
        $collection->method('getOne')->willReturn(
            $this->createMock(CollectionInterface::class)
        );
        $collection->method('getAll')->will($this->returnCallback(function () {
            yield 'foo' => $this->createMock(CollectionInterface::class);
        }));
        $collection->method('has')->willReturn(true);

        $this->namespace = new ResourceNamespace('foo', $this->createMock(ResourceNamespaceFactory::class), $collection, [
            '_id' => new ObjectId(),
            'name' => 'foo',
            'version' => 1,
        ]);
    }

    public function testGetCollection()
    {
        $this->assertInstanceOf(CollectionInterface::class, $this->namespace->getCollection('foo'));
    }

    public function testGetCollections()
    {
        $this->assertCount(1, iterator_to_array($this->namespace->getCollections()));
    }

    public function testHasCollection()
    {
        $this->assertTrue($this->namespace->hasCollection('bar'));
    }

    public function testGetName()
    {
        $this->assertSame('foo', $this->namespace->getName());
    }

    public function testGetIdentifier()
    {
        $this->assertSame('foo', $this->namespace->getIdentifier());
    }

    public function testDecorate()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($this->createMock(UriInterface::class));

        $result = $this->namespace->decorate($request);
        $this->assertSame('foo', $result['name']);
        $this->assertSame('Namespace', $result['kind']);
    }
}
