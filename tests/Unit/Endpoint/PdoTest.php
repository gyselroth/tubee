<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Unit\Endpoint;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Endpoint\Pdo;
use Tubee\Endpoint\Pdo\Wrapper as PdoWrapper;
use Tubee\Workflow\Factory as WorkflowFactory;

class PdoTest extends TestCase
{
    public function testSetupDefaultSettings()
    {
        $wrapper = $this->createMock(PdoWrapper::class);
        //$client->expects($this->exactly(1))->method('connect');
        $pdo = new Pdo('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $wrapper, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $pdo->setup();
    }

    public function testShutdown()
    {
        $wrapper = $this->createMock(PdoWrapper::class);
        //$client->expects($this->exactly(1))->method('connect');
        $pdo = new Pdo('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $wrapper, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $pdo->shutdown();
    }

    public function testTransformAndQuery()
    {
        $pdo = new Pdo('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $this->createMock(PdoWrapper::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));

        $query = [
            '$and' => [
                ['foo' => 'bar', 'foobar' => 'foobar'],
                ['bar' => 'foo', 'barf' => 'barf'],
            ],
        ];

        $efilter = '(foo= ? AND foobar= ?) AND (bar= ? AND barf= ?)';
        $evalues = ['bar', 'foobar', 'foo', 'barf'];

        list($filter, $values) = $pdo->transformQuery($query);
        $this->assertSame($efilter, $filter);
        $this->assertSame($evalues, $values);
    }
}
