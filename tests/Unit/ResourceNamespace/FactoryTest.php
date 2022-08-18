<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2022 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Unit\ResourceNamespace;

use Helmich\MongoMock\MockDatabase;
use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Tubee\Collection\Factory as CollectionFactory;
use Tubee\Resource\Factory as ResourceFactory;
use Tubee\ResourceNamespace\Exception;
use Tubee\ResourceNamespace\Factory as ResourceNamespaceFactory;
use Tubee\ResourceNamespace\ResourceNamespaceInterface;

class FactoryTest extends TestCase
{
    protected $factory;

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
        $this->factory = new ResourceNamespaceFactory($db, $this->createMock(CollectionFactory::class), $resource_factory);
    }

    public function testAdd()
    {
        $id = $this->factory->add([
            'name' => 'foo',
        ]);

        $this->assertInstanceOf(ObjectId::class, $id);
    }

    public function testAddNotUnique()
    {
        $this->expectException(Exception\NotUnique::class);
        $this->factory->add(['name' => 'foo']);
        $this->factory->add(['name' => 'foo']);
    }

    public function testGetOne()
    {
        $this->factory->add(['name' => 'foo']);
        $result = $this->factory->getOne('foo');
        $this->assertInstanceOf(ResourceNamespaceInterface::class, $result);
        $this->assertSame('foo', $result->getName());
    }

    public function testGetOneNotFound()
    {
        $this->expectException(Exception\NotFound::class);
        $this->factory->getOne('foo');
    }

    public function testGetAll()
    {
        $this->factory->add(['name' => 'foo']);
        $this->factory->add(['name' => 'bar']);
        $this->assertCount(2, iterator_to_array($this->factory->getAll()));
    }

    public function testGetAllQuery()
    {
        $this->factory->add(['name' => 'foo']);
        $this->factory->add(['name' => 'bar']);
        $this->assertCount(1, iterator_to_array($this->factory->getAll(['name' => ['$in' => ['foo']]])));
    }

    public function testGetAllPaging()
    {
        $this->factory->add(['name' => 'foo']);
        $this->factory->add(['name' => 'bar']);
        $result = iterator_to_array($this->factory->getAll([], 1, 1));
        $this->assertCount(1, $result);
        //$this->assertSame('bar', $result['bar']->getName());
    }

    public function testHas()
    {
        $this->factory->add(['name' => 'foo']);
        $this->assertTrue($this->factory->has('foo'));
    }

    public function testHasNot()
    {
        $this->assertFalse($this->factory->has('foo'));
    }

    public function testDelete()
    {
        $this->factory->add(['name' => 'foo']);
        $this->assertTrue($this->factory->deleteOne('foo'));
    }

    public function testDeleteNotFound()
    {
        $this->expectException(Exception\NotFound::class);
        $this->assertTrue($this->factory->deleteOne('foo'));
    }
}
