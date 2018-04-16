<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Unit;

use DateTime;
use Helmich\MongoMock\MockDatabase;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tubee\DataType;
use Tubee\DataType\DataTypeInterface;
use Tubee\DataType\Exception;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Mandator\MandatorInterface;

class DataTypeTest extends TestCase
{
    protected $datatype;

    public function setUp()
    {
        $mandator = $this->createMock(MandatorInterface::class);
        $mandator->method('getIdentifier')->willReturn('foo');
        $mandator->method('getName')->willReturn('foo');

        $db = new MockDatabase('foobar', [
            'typeMap' => [
                'root' => 'array',
                'document' => 'array',
                'array' => 'array',
            ],
        ]);

        $this->datatype = new DataType('foo', $mandator, $db, $this->createMock(LoggerInterface::class));
    }

    public function testInjectEndpoint()
    {
        $endpoint = $this->createMock(EndpointInterface::class);
        $this->assertInstanceOf(DataTypeInterface::class, $this->datatype->injectEndpoint($endpoint, 'foo'));
    }

    public function testInjectEndpointNotUnique()
    {
        $this->expectException(Exception\EndpointNotUnique::class);
        $endpoint = $this->createMock(EndpointInterface::class);
        $this->datatype->injectEndpoint($endpoint, 'foo');
        $this->datatype->injectEndpoint($endpoint, 'foo');
    }

    public function testGetEndpoint()
    {
        $endpoint = $this->createMock(EndpointInterface::class);
        $this->datatype->injectEndpoint($endpoint, 'foo');
        $this->assertSame($endpoint, $this->datatype->getEndpoint('foo'));
    }

    public function testGetEndpointNotFound()
    {
        $this->expectException(Exception\EndpointNotFound::class);
        $this->datatype->getEndpoint('foo');
    }

    public function testGetEndpoints()
    {
        $endpoint = $this->createMock(EndpointInterface::class);
        $this->datatype->injectEndpoint($endpoint, 'foo');
        $this->assertSame(['foo' => $endpoint], $this->datatype->getEndpoints());
    }

    public function testGetSourceEndpoints()
    {
        $endpoint = $this->createMock(EndpointInterface::class);
        $endpoint->method('getType')->willReturn(EndpointInterface::TYPE_SOURCE);

        $this->datatype->injectEndpoint($endpoint, 'foo');
        $this->assertSame(['foo' => $endpoint], $this->datatype->getSourceEndpoints());
    }

    public function testGetDestinationEndpoints()
    {
        $endpoint = $this->createMock(EndpointInterface::class);
        $endpoint->method('getType')->willReturn(EndpointInterface::TYPE_DESTINATION);

        $this->datatype->injectEndpoint($endpoint, 'foo');
        $this->assertSame(['foo' => $endpoint], $this->datatype->getDestinationEndpoints());
    }

    public function testGetEndpointsFiltered()
    {
        $endpoint = $this->createMock(EndpointInterface::class);
        $this->datatype->injectEndpoint($endpoint, 'bar');
        $this->datatype->injectEndpoint($endpoint, 'foo');
        $this->assertSame(['foo' => $endpoint], $this->datatype->getEndpoints(['foo']));
    }

    public function testGetEndpointsFilteredNotFound()
    {
        $this->expectException(Exception\EndpointNotFound::class);
        $endpoint = $this->createMock(EndpointInterface::class);
        $this->datatype->getEndpoints(['foo']);
    }

    public function testHasEndpoint()
    {
        $endpoint = $this->createMock(EndpointInterface::class);
        $this->datatype->injectEndpoint($endpoint, 'bar');
        $this->assertTrue($this->datatype->hasEndpoint('bar'));
    }

    public function testGetName()
    {
        $this->assertSame('foo', $this->datatype->getName());
    }

    public function testGetIdentifier()
    {
        $this->assertSame('foo::foo', $this->datatype->getIdentifier());
    }

    public function testCreateObjectSimulate()
    {
        $this->expectException(Exception\ObjectNotFound::class);
        $id = $this->datatype->create(['foo' => 'bar'], true);
        $this->assertInstanceOf(ObjectId::class, $id);
        $this->datatype->getOne(['_id' => $id], false);
    }

    public function testCreateObject()
    {
        $id = $this->datatype->create(['foo' => 'bar']);
        $this->assertInstanceOf(ObjectId::class, $id);
        $object = $this->datatype->getOne(['_id' => $id], false);
        $this->assertEquals($id, $object->getId());
        $this->assertSame('bar', $object->getData()['foo']);
    }

    public function testGetOneMultipleFound()
    {
        $this->expectException(Exception\ObjectMultipleFound::class);
        $this->datatype->create(['foo' => 'bar']);
        $this->datatype->create(['foo' => 'bar']);
        $object = $this->datatype->getOne(['data.foo' => 'bar'], false);
    }

    public function testGetAllNoFilter()
    {
        $id1 = $this->datatype->create(['foo' => 'bar']);
        $id2 = $this->datatype->create(['foo' => 'foo']);
        $objects = $this->datatype->getAll([], false);
        $objects = iterator_to_array($objects);
        $this->assertEquals('bar', $objects[(string) $id1]->getData()['foo']);
        $this->assertEquals('foo', $objects[(string) $id2]->getData()['foo']);
    }

    public function testGetAllNoObjects()
    {
        $objects = $this->datatype->getAll([], false);
        $objects = iterator_to_array($objects);
        $this->assertEmpty($objects);
    }

    public function testDeleteObjectSimulate()
    {
        $id = $this->datatype->create(['foo' => 'bar']);
        $this->datatype->delete($id, true);
        $object = $this->datatype->getOne(['data.foo' => 'bar'], false);
        $this->assertSame('bar', $object->getData()['foo']);
    }

    public function testDeleteObject()
    {
        $this->expectException(Exception\ObjectNotFound::class);
        $id = $this->datatype->create(['foo' => 'bar']);
        $this->datatype->delete($id);
        $object = $this->datatype->getOne(['data.foo' => 'bar'], false);
    }

    public function testUpdateObjectSimulate()
    {
        $id = $this->datatype->create(['foo' => 'bar']);
        $object = $this->datatype->getOne(['_id' => $id], false);
        $version = $this->datatype->change($object, ['foo' => 'foo'], true);
        $this->assertSame(--$version, $object->getVersion());
        $object = $this->datatype->getOne(['data.foo' => 'bar'], false);
        $this->assertSame('bar', $object->getData()['foo']);
    }

    public function testUpdateObject()
    {
        $id = $this->datatype->create(['foo' => 'bar']);
        $object = $this->datatype->getOne(['_id' => $id], false);
        $version = $this->datatype->change($object, ['foo' => 'foo']);
        $this->assertSame(--$version, $object->getVersion());
        $object = $this->datatype->getOne(['data.foo' => 'foo'], false);
        $this->assertSame('foo', $object->getData()['foo']);
    }

    public function testUpdateObjectNoChange()
    {
        $id = $this->datatype->create(['foo' => 'bar']);
        $object = $this->datatype->getOne(['_id' => $id], false);
        $version = $this->datatype->change($object, ['foo' => 'bar']);
        $this->assertSame($version, $object->getVersion());
    }

    public function testGetSpecificObjectVersion()
    {
        $id = $this->datatype->create(['foo' => 'bar']);
        $object = $this->datatype->getOne(['_id' => $id], false);
        $version = $this->datatype->change($object, ['foo' => 'foo']);
        $object = $this->datatype->getOne(['_id' => $id], false, 1);
        $this->assertSame(1, $object->getVersion());
        $this->assertSame('bar', $object->getData()['foo']);
    }

    public function testFlushSimulate()
    {
        $this->datatype->create(['foo' => 'bar']);
        $this->datatype->flush(true);
        $object = $this->datatype->getOne(['data.foo' => 'bar'], false);
        $this->assertSame('bar', $object->getData()['foo']);
    }

    public function testFlush()
    {
        $this->expectException(Exception\ObjectNotFound::class);
        $this->datatype->create(['foo' => 'bar']);
        $this->datatype->flush();
        $object = $this->datatype->getOne(['data.foo' => 'bar'], false);
    }

    public function testNewDataObject()
    {
        $id = $this->datatype->create(['foo' => 'bar']);
        $object = $this->datatype->getOne(['_id' => $id], false);
        $this->assertInstanceOf(UTCDateTime::class, $object->getCreated());
        $this->assertSame(1, $object->getVersion());
        $this->assertSame(['foo' => 'bar'], $object->getData());
        $this->assertSame($id, $object->getId());
        $this->assertNull($object->getDeleted());
    }

    public function testDataObjectAttributeDecorator()
    {
        $id = $this->datatype->create(['foo' => 'bar']);
        $object = $this->datatype->getOne(['_id' => $id], false)->decorate();
        $this->assertSame((string) $id, $object['id']);
        $this->assertSame(1, $object['version']);
        $this->assertSame('bar', $object['data']['foo']);
        $this->assertInstanceOf(DateTime::class, new DateTime($object['created']));
    }

    public function testDataObjectAttributeDecoratorFiltered()
    {
        $id = $this->datatype->create(['foo' => 'bar']);
        $object = $this->datatype->getOne(['_id' => $id], false)->decorate(['data']);
        $this->assertSame(['data' => ['foo' => 'bar']], $object);
    }
}
