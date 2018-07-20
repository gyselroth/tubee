<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Tubee\DataType\DataTypeInterface;
use Tubee\Mandator;
use Tubee\Mandator\Exception;
use Tubee\DataType\Factory as DataTypeFactory;
use MongoDB\BSON\ObjectId;

class MandatorTest extends TestCase
{
    protected $mandator;

    public function setUp()
    {
        $datatype = $this->createMock(DataTypeFactory::class);
        $datatype->method('getOne')->willReturn(
            $this->createMock(DataTypeInterface::class)
        );
        $datatype->method('getAll')->will($this->returnCallback(function() {
            yield 'foo' => $this->createMock(DataTypeInterface::class);
        }));
        $datatype->method('has')->willReturn(true);

        $this->mandator = new Mandator('foo', $datatype, [
            '_id' => new ObjectId(),
            'name' => 'foo',
            'version' => 1,
        ]);
    }

    public function testGetDataType()
    {
        $this->assertInstanceOf(DataTypeInterface::class, $this->mandator->getDataType('foo'));
    }

    public function testGetDataTypes()
    {
        $this->assertCount(1, iterator_to_array($this->mandator->getDataTypes()));
    }

    public function testHasDataType()
    {
        $this->assertTrue($this->mandator->hasDataType('bar'));
    }

    public function testGetName()
    {
        $this->assertSame('foo', $this->mandator->getName());
    }

    public function testGetIdentifier()
    {
        $this->assertSame('foo', $this->mandator->getIdentifier());
    }

    public function testDecorate()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($this->createMock(UriInterface::class));

        $result = $this->mandator->decorate($request);
        $this->assertSame('foo', $result['name']);
        $this->assertSame('Mandator', $result['kind']);
    }
}
