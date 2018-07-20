<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Unit\Mandator;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tubee\Mandator\Exception;
use Tubee\Mandator\MandatorInterface;
use Tubee\Mandator\Factory as MandatorFactory;
use Tubee\DataType\Factory as DataTypeFactory;
use MongoDB\BSON\ObjectId;
use Helmich\MongoMock\MockDatabase;

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

        $this->factory = new MandatorFactory($db, $this->createMock(DataTypeFactory::class));
    }

    public function testAdd()
    {
        $id = $this->factory->add([
            'name' => 'foo'
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
        $this->assertInstanceOf(MandatorInterface::class, $result);
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
        $this->assertTrue($this->factory->delete('foo'));
    }

    public function testDeleteNotFound()
    {
        $this->expectException(Exception\NotFound::class);
        $this->assertTrue($this->factory->delete('foo'));
    }
}
