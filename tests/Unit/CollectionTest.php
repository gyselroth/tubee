<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Unit;

use Helmich\MongoMock\MockDatabase;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\ObjectIdInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Tubee\Collection;
use Tubee\DataObject\Exception as ObjectException;
use Tubee\DataObject\Factory as DataObjectFactory;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Endpoint\Factory as EndpointFactory;
use Tubee\ResourceNamespace\ResourceNamespaceInterface;
use Tubee\Schema\SchemaInterface;

class CollectionTest extends TestCase
{
    protected $datatype;

    public function setUp()
    {
        $mandator = $this->createMock(ResourceNamespaceInterface::class);
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

        $this->datatype = new Collection('foo', $mandator, $endpoint_factory, $object_factory, $schema, $this->createMock(LoggerInterface::class), [
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

    /*public function testCreateObjectSimulate()
    {
        $this->expectException(ObjectException\NotFound::class);
        $id = $this->datatype->createObject(['foo' => 'bar'], true);
        $this->assertInstanceOf(ObjectIdInterface::class, $id);
        var_dump($this->datatype->getObject(['_id' => $id], false));
    }*/

    public function testCreateObject()
    {
        $id = $this->datatype->createObject(['foo' => 'bar']);
        $this->assertInstanceOf(ObjectIdInterface::class, $id);
    }

    public function testDecorate()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($this->createMock(UriInterface::class));

        $result = $this->datatype->decorate($request);
        $this->assertSame('foo', $result['name']);
        $this->assertSame('Collection', $result['kind']);
    }
}
