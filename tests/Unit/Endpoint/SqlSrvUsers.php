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
use Tubee\Endpoint\SqlSrvUsers\Wrapper;
use Tubee\Endpoint\SqlSrvUsers;
use Tubee\Endpoint\SqlSrvUsers\Wrapper as SqlSrvWrapper;
use Tubee\Workflow\Factory as WorkflowFactory;

class SqlSrvUsersTest extends TestCase
{
    public function testSetupDefaultSettings()
    {
        $wrapper = $this->createMock(SqlSrvWrapper::class);
        $sqlSrvUsers = new SqlSrvUsers('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $wrapper, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $sqlSrvUsers->setup();
    }

    public function testShutdown()
    {
        $wrapper = $this->createMock(SqlSrvWrapper::class);
        $sqlSrvUsers = new SqlSrvUsers('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $wrapper, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $sqlSrvUsers->shutdown();
    }

    public function testTransformAndQuery()
    {
        $sqlSrvUsers = new SqlSrvUsers('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $this->createMock(Wrapper::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));

        $query = [
            '$and' => [
                ['foo' => 'bar', 'foobar' => 'foobar'],
                ['bar' => 'foo', 'barf' => 'barf'],
            ],
        ];

        $efilter = '(foo= ? AND foobar= ?) AND (bar= ? AND barf= ?)';
        $evalues = ['bar', 'foobar', 'foo', 'barf'];

        [$filter, $values] = $sqlSrvUsers->transformQuery($query);
        $this->assertSame($efilter, $filter);
        $this->assertSame($evalues, $values);
    }
//
//    public function testTransformIsNull()
//    {
//        $pdo = new Pdo('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $this->createMock(PdoWrapper::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
//
//        $query = [
//            'foo' => null,
//        ];
//
//        $efilter = '(foo IS NULL)';
//        $evalues = [];
//
//        list($filter, $values) = $pdo->transformQuery($query);
//        $this->assertSame($efilter, $filter);
//        $this->assertSame($evalues, $values);
//    }
//
//    public function testTransformNotNull()
//    {
//        $pdo = new Pdo('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $this->createMock(PdoWrapper::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
//
//        $query = [
//            'foo' => ['$ne' => null],
//        ];
//
//        $efilter = '(foo IS NOT NULL)';
//        $evalues = [];
//
//        list($filter, $values) = $pdo->transformQuery($query);
//        $this->assertSame($efilter, $filter);
//        $this->assertSame($evalues, $values);
//    }
}
