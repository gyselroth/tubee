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
use Tubee\DataType\DataTypeInterface;
use Tubee\Manager;
use Tubee\Mandator;
use Tubee\Mandator\Exception;

class MandatorTest extends TestCase
{
    protected $mandator;

    public function setUp()
    {
        $this->mandator = new Mandator('foo', $this->createMock(Manager::class), $this->createMock(LoggerInterface::class));
    }

    public function testInjectDataType()
    {
        $datatype = $this->createMock(DataTypeInterface::class);
        $this->assertInstanceOf(Mandator::class, $this->mandator->injectDataType($datatype, 'foo'));
    }

    public function testInjectDataTypeNotUnique()
    {
        $this->expectException(Exception\DataTypeNotUnique::class);
        $datatype = $this->createMock(DataTypeInterface::class);
        $this->mandator->injectDataType($datatype, 'foo');
        $this->mandator->injectDataType($datatype, 'foo');
    }

    public function testGetDataType()
    {
        $datatype = $this->createMock(DataTypeInterface::class);
        $this->mandator->injectDataType($datatype, 'foo');
        $this->assertSame($datatype, $this->mandator->getDataType('foo'));
    }

    public function testGetDataTypeNotFound()
    {
        $this->expectException(Exception\DataTypeNotFound::class);
        $this->mandator->getDataType('foo');
    }

    public function testGetDataTypes()
    {
        $datatype = $this->createMock(DataTypeInterface::class);
        $this->mandator->injectDataType($datatype, 'foo');
        $this->assertSame(['foo' => $datatype], $this->mandator->getDataTypes());
    }

    public function testGetDataTypesFiltered()
    {
        $datatype = $this->createMock(DataTypeInterface::class);
        $this->mandator->injectDataType($datatype, 'bar');
        $this->mandator->injectDataType($datatype, 'foo');
        $this->assertSame(['foo' => $datatype], $this->mandator->getDataTypes(['foo']));
    }

    public function testGetDataTypesFilteredNotFound()
    {
        $this->expectException(Exception\DataTypeNotFound::class);
        $datatype = $this->createMock(DataTypeInterface::class);
        $this->mandator->getDataTypes(['foo']);
    }

    public function testHasDataType()
    {
        $datatype = $this->createMock(DataTypeInterface::class);
        $this->mandator->injectDataType($datatype, 'bar');
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
}
