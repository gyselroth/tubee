<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Unit\Endpoint;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\AbstractEndpoint;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Endpoint\Exception;
use Tubee\EndpointObject\EndpointObjectInterface;
use Tubee\Workflow\Factory as WorkflowFactory;

class AbstractEndpointTest extends TestCase
{
    public function testResolveFilterOne()
    {
        $mock = $this->getMockForAbstractClass(AbstractEndpoint::class, [
            'foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
                'data' => [
                    'options' => [
                        'filter_one' => '{foobar={map.foo.bar}}',
                    ],
                ],
            ],
        ]);

        $object = [
            'map' => [
                'foo' => [
                    'bar' => 'foo',
                    'foo' => 'bar',
                ],
            ],
        ];

        $result = $mock->getFilterOne($object);
        $this->assertSame('{foobar=foo}', $result);
    }

    public function testResolveFilterOneMultipleValues()
    {
        $mock = $this->getMockForAbstractClass(AbstractEndpoint::class, [
            'foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
                'data' => [
                    'options' => [
                        'filter_one' => '{foobar={map.foo.bar}}-{map.foo.foo}',
                    ],
                ],
            ],
        ]);

        $object = [
            'map' => [
                'foo' => [
                    'bar' => 'foo',
                    'foo' => 'bar',
                ],
            ],
        ];

        $result = $mock->getFilterOne($object);
        $this->assertSame('{foobar=foo}-bar', $result);
    }

    public function testResolveFilterOneAttributeNotFound()
    {
        $this->expectException(Exception\AttributeNotResolvable::class);
        $mock = $this->getMockForAbstractClass(AbstractEndpoint::class, [
            'foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
                'data' => [
                    'options' => [
                        'filter_one' => '{foobar={map.foo.bar}}',
                    ],
                ],
            ],
        ]);

        $object = [
            'map' => [
                'foo' => [
                    'foo' => 'bar',
                ],
            ],
        ];

        $mock->getFilterOne($object);
    }

    public function testObjectExists()
    {
        $mock = $this->getMockForAbstractClass(AbstractEndpoint::class, [
            'foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class),
        ]);

        $mock->expects($this->any())
             ->method('getOne')
             ->will($this->returnValue($this->createMock(EndpointObjectInterface::class)));
        $this->assertTrue($mock->exists([]));
    }

    public function testObjectExistsIfMultipleFound()
    {
        $mock = $this->getMockForAbstractClass(AbstractEndpoint::class, [
            'foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class),
        ]);

        $mock->expects($this->any())
             ->method('getOne')
             ->will($this->throwException(new Exception\ObjectMultipleFound()));
        $this->assertTrue($mock->exists([]));
    }

    public function testObjectNotExistsIfNotFound()
    {
        $mock = $this->getMockForAbstractClass(AbstractEndpoint::class, [
            'foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class),
        ]);

        $mock->expects($this->any())
             ->method('getOne')
             ->will($this->throwException(new Exception\ObjectNotFound()));
        $this->assertFalse($mock->exists([]));
    }
}
