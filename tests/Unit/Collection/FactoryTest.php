<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2022 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Unit\Collection;

use Helmich\MongoMock\MockDatabase;
use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Collection\Exception;
use Tubee\Collection\Factory as CollectionFactory;
use Tubee\DataObject\Factory as DataObjectFactory;
use Tubee\Endpoint\Factory as EndpointFactory;
use Tubee\Resource\Factory as ResourceFactory;
use Tubee\ResourceNamespace\ResourceNamespaceInterface;

class FactoryTest extends TestCase
{
    protected $factory;
    protected $namespace;

    public function setUp()
    {
        $db = new MockDatabase('foobar', [
            'typeMap' => [
                'root' => 'array',
                'document' => 'array',
                'array' => 'array',
            ],
        ]);

        $resource_factory = new ResourceFactory($this->createMock(LoggerInterface::class), $this->createMock(CacheInterface::class));
        $this->factory = new CollectionFactory($db, $resource_factory, $this->createMock(EndpointFactory::class), $this->createMock(DataObjectFactory::class), $this->createMock(LoggerInterface::class));
        $this->namespace = $this->getResourceNamespaceMock();
    }

    public function testAdd()
    {
        $id = $this->factory->add($this->namespace, $this->getCollectionDefinition('foo'));
        $this->assertInstanceOf(ObjectId::class, $id);
    }

    public function testAddNotUnique()
    {
        $this->expectException(Exception\NotUnique::class);
        $this->factory->add($this->namespace, $this->getCollectionDefinition('foo'));
        $this->factory->add($this->namespace, $this->getCollectionDefinition('foo'));
    }

    public function testGetOne()
    {
        $this->factory->add($this->namespace, $this->getCollectionDefinition('foo'));
        $resource = $this->factory->getOne($this->namespace, 'foo');
        $this->assertInstanceOf(CollectionInterface::class, $resource);
        $this->assertSame('foo', $resource->getName());
        $this->assertSame('foo', $resource->getResourceNamespace()->getName());
    }

    public function testGetOneNotFound()
    {
        $this->expectException(Exception\NotFound::class);
        $this->factory->getOne($this->namespace, 'foo');
    }

    public function testGetAll()
    {
        $this->factory->add($this->namespace, $this->getCollectionDefinition('foo'));
        $this->factory->add($this->namespace, $this->getCollectionDefinition('bar'));
        $this->assertCount(2, iterator_to_array($this->factory->getAll($this->namespace)));
    }

    public function testGetAllQuery()
    {
        $this->factory->add($this->namespace, $this->getCollectionDefinition('foo'));
        $this->factory->add($this->namespace, $this->getCollectionDefinition('bar'));
        $this->assertCount(1, iterator_to_array($this->factory->getAll($this->namespace, ['name' => ['$in' => ['foo']]])));
    }

    public function testGetAllPaging()
    {
        $this->factory->add($this->namespace, $this->getCollectionDefinition('foo'));
        $this->factory->add($this->namespace, $this->getCollectionDefinition('bar'));
        $result = iterator_to_array($this->factory->getAll($this->namespace, [], 1, 1));
        $this->assertCount(1, $result);
        //$this->assertSame('bar', $result['bar']->getName());
    }

    public function testHas()
    {
        $this->factory->add($this->namespace, $this->getCollectionDefinition('foo'));
        $this->assertTrue($this->factory->has($this->namespace, 'foo'));
    }

    public function testHasNot()
    {
        $this->assertFalse($this->factory->has($this->namespace, 'foo'));
    }

    /*public function testDelete()
    {
        $this->resource_factory
            ->expects($this->once())
            ->method('getOne')
            ->willReturn([
                'name' => 'foo'
            ]);

        $this->resource_factory
            ->expects($this->once())
            ->method('deleteFrom')
            ->willReturn([
                'name' => 'foo'
            ]);

        $this->assertTrue($this->factory->deleteOne($this->namespace, 'foo'));
    }*/

    public function testDeleteNotFound()
    {
        $this->expectException(Exception\NotFound::class);
        $this->assertTrue($this->factory->deleteOne($this->namespace, 'foo'));
    }

    protected function getResourceNamespaceMock()
    {
        $mock = $this->createMock(ResourceNamespaceInterface::class);
        $mock->method('getName')->willReturn('foo');

        return $mock;
    }

    protected function getCollectionDefinition($name)
    {
        return [
            'name' => $name,
            'data' => [
                'schema' => [],
            ],
        ];
    }
}
