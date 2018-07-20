<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Unit\DataType;

use Helmich\MongoMock\MockDatabase;
use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tubee\DataType\DataTypeInterface;
use Tubee\DataType\Exception;
use Tubee\DataType\Factory as DataTypeFactory;
use Tubee\Endpoint\Factory as EndpointFactory;
use Tubee\Mandator\MandatorInterface;

class FactoryTest extends TestCase
{
    protected $factory;
    protected $mandator;

    public function setUp()
    {
        $db = new MockDatabase('foobar', [
            'typeMap' => [
                'root' => 'array',
                'document' => 'array',
                'array' => 'array',
            ],
        ]);

        $this->factory = new DataTypeFactory($db, $this->createMock(EndpointFactory::class), $this->createMock(LoggerInterface::class));
        $this->mandator = $this->getMandatorMock();
    }

    public function testAdd()
    {
        $id = $this->factory->add($this->mandator, $this->getDataTypeDefinition('foo'));
        $this->assertInstanceOf(ObjectId::class, $id);
    }

    public function testAddNotUnique()
    {
        $this->expectException(Exception\NotUnique::class);
        $this->factory->add($this->mandator, $this->getDataTypeDefinition('foo'));
        $this->factory->add($this->mandator, $this->getDataTypeDefinition('foo'));
    }

    public function testGetOne()
    {
        $this->factory->add($this->mandator, $this->getDataTypeDefinition('foo'));
        $resource = $this->factory->getOne($this->mandator, 'foo');
        $this->assertInstanceOf(DataTypeInterface::class, $resource);
        $this->assertSame('foo', $resource->getName());
        $this->assertSame('foo', $resource->getMandator()->getName());
    }

    public function testGetOneNotFound()
    {
        $this->expectException(Exception\NotFound::class);
        $this->factory->getOne($this->mandator, 'foo');
    }

    public function testGetAll()
    {
        $this->factory->add($this->mandator, $this->getDataTypeDefinition('foo'));
        $this->factory->add($this->mandator, $this->getDataTypeDefinition('bar'));
        $this->assertCount(2, iterator_to_array($this->factory->getAll($this->mandator)));
    }

    public function testGetAllQuery()
    {
        $this->factory->add($this->mandator, $this->getDataTypeDefinition('foo'));
        $this->factory->add($this->mandator, $this->getDataTypeDefinition('bar'));
        $this->assertCount(1, iterator_to_array($this->factory->getAll($this->mandator, ['name' => ['$in' => ['foo']]])));
    }

    public function testGetAllPaging()
    {
        $this->factory->add($this->mandator, $this->getDataTypeDefinition('foo'));
        $this->factory->add($this->mandator, $this->getDataTypeDefinition('bar'));
        $result = iterator_to_array($this->factory->getAll($this->mandator, [], 1, 1));
        $this->assertCount(1, $result);
        //$this->assertSame('bar', $result['bar']->getName());
    }

    public function testHas()
    {
        $this->factory->add($this->mandator, $this->getDataTypeDefinition('foo'));
        $this->assertTrue($this->factory->has($this->mandator, 'foo'));
    }

    public function testHasNot()
    {
        $this->assertFalse($this->factory->has($this->mandator, 'foo'));
    }

    public function testDelete()
    {
        $this->factory->add($this->mandator, $this->getDataTypeDefinition('foo'));
        $this->assertTrue($this->factory->delete($this->mandator, 'foo'));
    }

    public function testDeleteNotFound()
    {
        $this->expectException(Exception\NotFound::class);
        $this->assertTrue($this->factory->delete($this->mandator, 'foo'));
    }

    protected function getMandatorMock()
    {
        $mock = $this->createMock(MandatorInterface::class);
        $mock->method('getName')->willReturn('foo');

        return $mock;
    }

    protected function getDataTypeDefinition($name)
    {
        return [
            'name' => $name,
            'schema' => [],
        ];
    }
}
