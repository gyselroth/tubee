<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Unit;

use Helmich\MongoMock\MockDatabase;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Tubee\DataObject\Exception as ObjectException;
use Tubee\DataObject\Factory as DataObjectFactory;
use Tubee\DataType;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Endpoint\Factory as EndpointFactory;
use Tubee\Mandator\MandatorInterface;
use Tubee\Schema\SchemaInterface;

class DataTypeTest extends TestCase
{
    protected $datatype;

    public function setUp()
    {
        $mandator = $this->createMock(MandatorInterface::class);
        $mandator->method('getIdentifier')->willReturn('foo');
        $mandator->method('getName')->willReturn('foo');

        $schema = $this->createMock(SchemaInterface::class);
        $endpoint_factory = $this->createMock(EndpointFactory::class);
        $object_factory = $this->createMock(DataObjectFactory::class);

        $db = new MockDatabase('foobar', [
            'typeMap' => [
                'root' => 'array',
                'document' => 'array',
                'array' => 'array',
            ],
        ]);

        $this->datatype = new DataType('foo', $mandator, $endpoint_factory, $object_factory, $schema, $this->createMock(LoggerInterface::class), [
            '_id' => new ObjectId(),
            'version' => 1,
            'name' => 'foo',
        ]);
    }

    /*public function testGetSourceEndpoints()
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
    }*/

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
        $this->expectException(ObjectException\NotFound::class);
        $id = $this->datatype->createObject(['foo' => 'bar'], true);
        $this->assertInstanceOf(ObjectId::class, $id);
        $this->datatype->getObject(['_id' => $id], false);
    }

    public function testCreateObject()
    {
        $id = $this->datatype->createObject(['foo' => 'bar']);
        $this->assertInstanceOf(ObjectId::class, $id);
        $object = $this->datatype->getObject(['_id' => $id], false);
        $this->assertEquals($id, $object->getId());
        $this->assertSame('bar', $object->getData()['foo']);
    }

    public function testGetOneMultipleFound()
    {
        $this->expectException(ObjectException\MultipleFound::class);
        $this->datatype->createObject(['foo' => 'bar']);
        $this->datatype->createObject(['foo' => 'bar']);
        $object = $this->datatype->getObject(['data.foo' => 'bar'], false);
    }

    public function testGetAllObjectsNoFilter()
    {
        $id1 = $this->datatype->createObject(['foo' => 'bar']);
        $id2 = $this->datatype->createObject(['foo' => 'foo']);
        $objects = $this->datatype->getObjects([], false);
        $objects = iterator_to_array($objects);
        $this->assertEquals('bar', $objects[(string) $id1]->getData()['foo']);
        $this->assertEquals('foo', $objects[(string) $id2]->getData()['foo']);
    }

    public function testGetAllNoObjects()
    {
        $objects = $this->datatype->getObjects([], false);
        $objects = iterator_to_array($objects);
        $this->assertEmpty($objects);
    }

    public function testDeleteObjectSimulate()
    {
        $id = $this->datatype->createObject(['foo' => 'bar']);
        $this->datatype->delete($id, true);
        $object = $this->datatype->getObject(['data.foo' => 'bar'], false);
        $this->assertSame('bar', $object->getData()['foo']);
    }

    public function testDeleteObject()
    {
        $this->expectException(ObjectException\NotFound::class);
        $id = $this->datatype->createObject(['foo' => 'bar']);
        $this->datatype->delete($id);
        $object = $this->datatype->getObject(['data.foo' => 'bar'], false);
    }

    public function testUpdateObjectSimulate()
    {
        $id = $this->datatype->createObject(['foo' => 'bar']);
        $object = $this->datatype->getObject(['_id' => $id], false);
        $version = $this->datatype->change($object, ['foo' => 'foo'], true);
        $this->assertSame(--$version, $object->getVersion());
        $object = $this->datatype->getObject(['data.foo' => 'bar'], false);
        $this->assertSame('bar', $object->getData()['foo']);
    }

    public function testUpdateObject()
    {
        $id = $this->datatype->createObject(['foo' => 'bar']);
        $object = $this->datatype->getObject(['_id' => $id], false);
        $version = $this->datatype->change($object, ['foo' => 'foo']);
        $this->assertSame(--$version, $object->getVersion());
        $object = $this->datatype->getObject(['data.foo' => 'foo'], false);
        $this->assertSame('foo', $object->getData()['foo']);
    }

    public function testUpdateObjectNoChange()
    {
        $id = $this->datatype->createObject(['foo' => 'bar']);
        $object = $this->datatype->getObject(['_id' => $id], false);
        $version = $this->datatype->change($object, ['foo' => 'bar']);
        $this->assertSame($version, $object->getVersion());
    }

    public function testGetSpecificObjectVersion()
    {
        $id = $this->datatype->createObject(['foo' => 'bar']);
        $object = $this->datatype->getObject(['_id' => $id], false);
        $version = $this->datatype->change($object, ['foo' => 'foo']);
        $object = $this->datatype->getObject(['_id' => $id], false, 1);
        $this->assertSame(1, $object->getVersion());
        $this->assertSame('bar', $object->getData()['foo']);
    }

    public function testFlushSimulate()
    {
        $this->datatype->createObject(['foo' => 'bar']);
        $this->datatype->flush(true);
        $object = $this->datatype->getObject(['data.foo' => 'bar'], false);
        $this->assertSame('bar', $object->getData()['foo']);
    }

    public function testFlush()
    {
        $this->expectException(ObjectException\NotFound::class);
        $this->datatype->createObject(['foo' => 'bar']);
        $this->datatype->flush();
        $object = $this->datatype->getObject(['data.foo' => 'bar'], false);
    }

    public function testNewDataObject()
    {
        $id = $this->datatype->createObject(['foo' => 'bar']);
        $object = $this->datatype->getObject(['_id' => $id], false);
        $this->assertInstanceOf(UTCDateTime::class, $object->getCreated());
        $this->assertSame(1, $object->getVersion());
        $this->assertSame(['foo' => 'bar'], $object->getData());
        $this->assertSame($id, $object->getId());
        $this->assertNull($object->getDeleted());
    }

    public function testDecorate()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($this->createMock(UriInterface::class));

        $result = $this->datatype->decorate($request);
        $this->assertSame('foo', $result['name']);
        $this->assertSame('DataType', $result['kind']);
    }
}
