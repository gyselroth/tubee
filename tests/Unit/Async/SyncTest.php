<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Unit;

use MongoDB\BSON\ObjectId;
use MongoDB\Database;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use TaskScheduler\Scheduler;
use Tubee\Async\Sync;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\ResourceNamespace\Factory as ResourceNamespaceFactory;
use Tubee\ResourceNamespace\ResourceNamespaceInterface;

class SyncTest extends TestCase
{
    public function testSimpleSyncBrowseType()
    {
        $factory = $this->createMock(ResourceNamespaceFactory::class);
        $factory
            ->expects($this->once())
            ->method('getOne')
            ->with('foo')
            ->willReturn($this->getMockNamespace('foo'));

        $scheduler = $this->createMock(Scheduler::class);
        $scheduler->expects($this->never())->method('addJob');

        $sync = new Sync($factory, $this->createMock(Database::class), $scheduler, $this->createMock(Logger::class));
        $sync->setId(new ObjectId());
        $sync->setData([
            'namespace' => 'foo',
            'collections' => ['bar'],
            'endpoints' => ['foobar'],
            'log_level' => 'debug',
            'ignore' => false,
            'filter' => [],
            'simulate' => false,
            'notification' => [
                'enabled' => false,
                'receiver' => [],
            ],
        ]);

        $sync->start();
    }

    public function testSyncParalellCollections()
    {
        $factory = $this->createMock(ResourceNamespaceFactory::class);
        $factory
            ->expects($this->once())
            ->method('getOne')
            ->with('foo')
            ->willReturn($this->getMockNamespace('foo'));

        $id = new ObjectId();

        $data = [
            'namespace' => 'foo',
            'log_level' => 'debug',
            'ignore' => false,
            'filter' => [],
            'simulate' => false,
            'notification' => [
                'enabled' => false,
                'receiver' => [],
            ],
        ];

        $scheduler = $this->createMock(Scheduler::class);
        $data['parent'] = $id;
        $data['collections'] = ['bar'];
        $data['endpoints'] = ['foobar'];
        $scheduler->expects($this->at(0))
            ->method('addJob')
            ->with(Sync::class, $data);

        $data['collections'] = ['foo'];
        $scheduler->expects($this->at(1))
            ->method('addJob')
            ->with(Sync::class, $data);

        $scheduler->expects($this->exactly(2))->method('addJob');

        $sync = new Sync($factory, $this->createMock(Database::class), $scheduler, $this->createMock(Logger::class));
        $sync->setId($id);
        unset($data['parent']);
        $data['collections'] = [['bar', 'foo']];
        $data['endpoints'] = ['foobar'];

        $sync->setData($data);
        $sync->start();
    }

    public function testSyncNonParalellCollections()
    {
        $factory = $this->createMock(ResourceNamespaceFactory::class);
        $factory
            ->expects($this->once())
            ->method('getOne')
            ->with('foo')
            ->willReturn($this->getMockNamespace('foo'));

        $id = new ObjectId();

        $data = [
            'namespace' => 'foo',
            'log_level' => 'debug',
            'ignore' => false,
            'filter' => [],
            'simulate' => false,
            'notification' => [
                'enabled' => false,
                'receiver' => [],
            ],
        ];

        $scheduler = $this->createMock(Scheduler::class);
        $scheduler->expects($this->never())->method('addJob');

        $sync = new Sync($factory, $this->createMock(Database::class), $scheduler, $this->createMock(Logger::class));
        $sync->setId($id);
        $data['collections'] = ['bar', 'foo'];
        $data['endpoints'] = ['foobar'];

        $sync->setData($data);
        $sync->start();
    }

    public function testSyncParalellCollectionsAndEndpoints()
    {
        $factory = $this->createMock(ResourceNamespaceFactory::class);
        $factory
            ->expects($this->once())
            ->method('getOne')
            ->with('foo')
            ->willReturn($this->getMockNamespace('foo'));

        $id = new ObjectId();

        $data = [
            'namespace' => 'foo',
            'log_level' => 'debug',
            'ignore' => false,
            'filter' => [],
            'simulate' => false,
            'notification' => [
                'enabled' => false,
                'receiver' => [],
            ],
        ];

        $scheduler = $this->createMock(Scheduler::class);
        $data['parent'] = $id;
        $data['collections'] = ['bar'];
        $data['endpoints'] = ['foo'];
        $scheduler->expects($this->at(0))
            ->method('addJob')
            ->with(Sync::class, $data);

        $data['collections'] = ['bar'];
        $data['endpoints'] = ['bar'];
        $scheduler->expects($this->at(1))
            ->method('addJob')
            ->with(Sync::class, $data);

        $data['collections'] = ['foo'];
        $data['endpoints'] = ['foo'];
        $scheduler->expects($this->at(2))
            ->method('addJob')
            ->with(Sync::class, $data);

        $data['collections'] = ['foo'];
        $data['endpoints'] = ['bar'];
        $scheduler->expects($this->at(3))
            ->method('addJob')
            ->with(Sync::class, $data);

        $scheduler->expects($this->exactly(4))->method('addJob');

        $sync = new Sync($factory, $this->createMock(Database::class), $scheduler, $this->createMock(Logger::class));
        $sync->setId($id);
        unset($data['parent']);
        $data['collections'] = [['bar', 'foo']];
        $data['endpoints'] = [['foo', 'bar']];

        $sync->setData($data);
        $sync->start();
    }

    public function testSyncParalellCollectionsAndNonParalellEndpoints()
    {
        $factory = $this->createMock(ResourceNamespaceFactory::class);
        $factory
            ->expects($this->once())
            ->method('getOne')
            ->with('foo')
            ->willReturn($this->getMockNamespace('foo'));

        $id = new ObjectId();

        $data = [
            'namespace' => 'foo',
            'log_level' => 'debug',
            'ignore' => false,
            'filter' => [],
            'simulate' => false,
            'notification' => [
                'enabled' => false,
                'receiver' => [],
            ],
        ];

        $scheduler = $this->createMock(Scheduler::class);
        $data['parent'] = $id;
        $data['collections'] = ['bar'];
        $data['endpoints'] = ['foo'];
        $scheduler->expects($this->at(0))
            ->method('addJob')
            ->with(Sync::class, $data);

        $data['collections'] = ['foo'];
        $data['endpoints'] = ['foo'];
        $scheduler->expects($this->at(1))
            ->method('addJob')
            ->with(Sync::class, $data);

        $data['collections'] = ['bar'];
        $data['endpoints'] = ['bar'];
        $scheduler->expects($this->at(2))
            ->method('addJob')
            ->with(Sync::class, $data);

        $data['collections'] = ['foo'];
        $data['endpoints'] = ['bar'];
        $scheduler->expects($this->at(3))
            ->method('addJob')
            ->with(Sync::class, $data);

        $scheduler->expects($this->exactly(4))->method('addJob');

        $sync = new Sync($factory, $this->createMock(Database::class), $scheduler, $this->createMock(Logger::class));
        $sync->setId($id);
        unset($data['parent']);
        $data['collections'] = [['bar', 'foo']];
        $data['endpoints'] = ['foo', 'bar'];

        $sync->setData($data);
        $sync->start();
    }

    protected function getMockEndpoint($name)
    {
        $mock = $this->createMock(EndpointInterface::class);
        $mock
            ->method('getName')
            ->willReturn($name);

        return $mock;
    }

    protected function getMockCollection($name)
    {
        $that = $this;
        $mock = $this->createMock(CollectionInterface::class);
        $mock
            ->method('getEndpoints')
            ->will($this->returnCallback(function ($query) use ($that) {
                $endpoints = $query['name']['$in'];
                foreach ($endpoints as $endpoint) {
                    yield $that->getMockEndpoint($endpoint);
                }
            }));

        $mock->method('getName')->willReturn($name);

        return $mock;
    }

    protected function getMockNamespace($name)
    {
        $that = $this;
        $mock = $this->createMock(ResourceNamespaceInterface::class);
        $mock
            ->method('getCollections')
            ->will($this->returnCallback(function ($query) use ($that) {
                $collections = $query['name']['$in'];
                foreach ($collections as $collection) {
                    yield $that->getMockCollection($collection);
                }
            }));

        $mock->method('getName')->willReturn($name);

        return $mock;
    }
}
