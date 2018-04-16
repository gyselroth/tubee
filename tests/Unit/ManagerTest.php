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
use Tubee\Manager;
use Tubee\Manager\Exception;
use Tubee\Mandator\MandatorInterface;

class ManagerTest extends TestCase
{
    protected $manager;

    public function setUp()
    {
        $this->manager = new Manager($this->createMock(LoggerInterface::class));
    }

    public function testInjectMandator()
    {
        $mandator = $this->createMock(MandatorInterface::class);
        $this->assertInstanceOf(Manager::class, $this->manager->injectMandator($mandator, 'foo'));
    }

    public function testInjectMandatorNotUnique()
    {
        $this->expectException(Exception\MandatorNotUnique::class);
        $mandator = $this->createMock(MandatorInterface::class);
        $this->manager->injectMandator($mandator, 'foo');
        $this->manager->injectMandator($mandator, 'foo');
    }

    public function testGetMandator()
    {
        $mandator = $this->createMock(MandatorInterface::class);
        $this->manager->injectMandator($mandator, 'foo');
        $this->assertSame($mandator, $this->manager->getMandator('foo'));
    }

    public function testGetMandatorNotFound()
    {
        $this->expectException(Exception\MandatorNotFound::class);
        $this->manager->getMandator('foo');
    }

    public function testGetMandators()
    {
        $mandator = $this->createMock(MandatorInterface::class);
        $this->manager->injectMandator($mandator, 'foo');
        $this->assertSame(['foo' => $mandator], $this->manager->getMandators());
    }

    public function testGetMandatorsFiltered()
    {
        $mandator = $this->createMock(MandatorInterface::class);
        $this->manager->injectMandator($mandator, 'bar');
        $this->manager->injectMandator($mandator, 'foo');
        $this->assertSame(['foo' => $mandator], $this->manager->getMandators(['foo']));
    }

    public function testGetMandatorsFilteredNotFound()
    {
        $this->expectException(Exception\MandatorNotFound::class);
        $mandator = $this->createMock(MandatorInterface::class);
        $this->manager->getMandators(['foo']);
    }

    public function testHasMandator()
    {
        $mandator = $this->createMock(MandatorInterface::class);
        $this->manager->injectMandator($mandator, 'bar');
        $this->assertTrue($this->manager->hasMandator('bar'));
    }
}
