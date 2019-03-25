<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Unit\Endpoint;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Endpoint\Exception;
use Tubee\Endpoint\Ucs;
use Tubee\Endpoint\Ucs\Exception as UcsException;
use Tubee\Workflow\Factory as WorkflowFactory;

class UcsTest extends TestCase
{
    public function testSetupDefaultSettings()
    {
        $jar = $this->createMock(CookieJar::class);
        $jar->expects($this->once())
            ->method('toArray')
            ->willReturn([
                [
                    'Name' => Ucs::SESSION_COOKIE_NAME,
                    'Value' => 'foo',
                ],
            ]);

        $body = $this->createMock(StreamInterface::class);
        $body->expects($this->once())
            ->method('getContents')
            ->willReturn('{}');

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($body);

        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('__call')
            ->with(
                $this->equalTo('post')
            )->willReturn($response);

        $client->expects($this->once())
            ->method('getConfig')
            ->with(
                $this->equalTo('cookies')
            )
            ->willReturn($jar);

        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => [
                'resource' => [
                    'base_uri' => 'http://foo.bar',
                    'auth' => [
                        'username' => 'foo',
                        'password' => 'bar',
                    ],
                ],
            ],
        ]);

        $ucs->setup();
    }

    public function testSetupAuthInvalidCookie()
    {
        $this->expectException(UcsException\SessionCookieNotAvailable::class);
        $jar = $this->createMock(CookieJar::class);
        $jar->expects($this->once())
            ->method('toArray')
            ->willReturn([]);

        $body = $this->createMock(StreamInterface::class);
        $body->expects($this->once())
            ->method('getContents')
            ->willReturn('{}');

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($body);

        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('__call')
            ->with(
                $this->equalTo('post')
            )->willReturn($response);

        $client->expects($this->once())
            ->method('getConfig')
            ->with(
                $this->equalTo('cookies')
            )
            ->willReturn($jar);

        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => [
                'resource' => [
                    'base_uri' => 'http://foo.bar',
                    'auth' => [
                        'username' => 'foo',
                        'password' => 'bar',
                    ],
                ],
            ],
        ]);

        $ucs->setup();
    }

    public function testShutdown()
    {
        $jar = $this->createMock(CookieJar::class);
        $jar->expects($this->once())
            ->method('clear')
            ->willReturn(true);

        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('getConfig')
            ->with(
                $this->equalTo('cookies')
            )
            ->willReturn($jar);

        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $ucs->shutdown();
    }

    public function testGetOne()
    {
        $response = [
            'status' => 200,
            'result' => [
                [
                    '$dn$' => 'cn=foo,ou=bar',
                    'foo' => 'bar',
                ],
            ],
        ];

        $client = $this->getMockClient($response);

        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => ['options' => ['filter_one' => json_encode([
                'bar' => 'bar',
            ])]],
        ]);

        $result = $ucs->getOne([])->getData();
        $this->assertSame('bar', $result['foo']);
    }

    public function testGetOneInvalidUcsFilter()
    {
        $this->expectException(UcsException\InvalidFilter::class);
        $client = $this->createMock(Client::class);
        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => ['options' => ['filter_one' => '']],
        ]);

        $ucs->getOne([]);
    }

    public function testGetOneMultipleFound()
    {
        $this->expectException(Exception\ObjectMultipleFound::class);
        $response = [
            'status' => 200,
            'result' => [
                [
                    '$dn$' => 'cn=foo,ou=bar',
                    'foo' => 'bar',
                ],
                [
                    '$dn$' => 'cn=bar,ou=bar',
                    'foo' => 'bar',
                ],
            ],
        ];

        $client = $this->getMockClient($response);

        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => ['options' => ['filter_one' => json_encode([
                'bar' => 'bar',
            ])]],
        ]);

        $ucs->getOne([]);
    }

    public function testGetOneNotFound()
    {
        $this->expectException(Exception\ObjectNotFound::class);
        $response = [
            'status' => 200,
            'result' => [],
        ];

        $client = $this->getMockClient($response);

        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => ['options' => ['filter_one' => json_encode([
                'objectProperty' => 'bar',
                'objectPropertyValue' => 'bar',
            ])]],
        ]);

        $ucs->getOne([]);
    }

    public function testGetDiffNoChange()
    {
        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $this->createMock(Client::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $result = $ucs->getDiff($this->createMock(AttributeMapInterface::class), []);
        $this->assertSame([], $result);
    }

    public function testGetDiffReplaceValue()
    {
        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $this->createMock(Client::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $diff = [
            'foo' => [
                'attribute' => 'foo',
                'action' => AttributeMapInterface::ACTION_REPLACE,
                'value' => 'bar',
            ],
        ];

        $result = $ucs->getDiff($this->createMock(AttributeMapInterface::class), $diff);
        $expected = [
            'foo' => 'bar',
        ];

        $this->assertSame($expected, $result);
    }

    public function testGetDiffReplaceTwoExistingValue()
    {
        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $this->createMock(Client::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $diff = [
            'foo' => [
                'attribute' => 'foo',
                'action' => AttributeMapInterface::ACTION_REPLACE,
                'value' => 'bar',
            ],
            'bar' => [
                'attribute' => 'foo',
                'action' => AttributeMapInterface::ACTION_REPLACE,
                'value' => 'foo',
            ],
        ];

        $result = $ucs->getDiff($this->createMock(AttributeMapInterface::class), $diff);
        $expected = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];

        $this->assertSame($expected, $result);
    }

    public function testGetDiffRemoveValue()
    {
        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $this->createMock(Client::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $diff = [
            'foo' => [
                'action' => AttributeMapInterface::ACTION_REMOVE,
            ],
        ];

        $result = $ucs->getDiff($this->createMock(AttributeMapInterface::class), $diff);
        $expected = [
            'foo' => '',
        ];

        $this->assertSame($expected, $result);
    }

    public function testGetDiffAddValue()
    {
        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $this->createMock(Client::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $diff = [
            'foo' => [
                'action' => AttributeMapInterface::ACTION_ADD,
                'value' => 'bar',
            ],
        ];

        $result = $ucs->getDiff($this->createMock(AttributeMapInterface::class), $diff);
        $expected = [
            'foo' => 'bar',
        ];

        $this->assertSame($expected, $result);
    }

    public function testCreateObject()
    {
        $response = [
            'result' => [
                [
                    '$dn$' => 'uid=foo,ou=bar',
                    'success' => true,
                ],
            ],
        ];

        $client = $this->getMockClient($response);
        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $object = [
            '$dn$' => 'uid=foo,ou=bar',
            'foo' => 'bar',
        ];

        $result = $ucs->create($this->createMock(AttributeMapInterface::class), $object);
        $this->assertSame('uid=foo,ou=bar', $result);
    }

    public function testCreateObjectInvalidResponse()
    {
        $this->expectException(Exception\NotIterable::class);
        $response = [
            'result' => 'foo',
        ];

        $client = $this->getMockClient($response);
        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $object = [
            '$dn$' => 'uid=foo,ou=bar',
            'foo' => 'bar',
        ];

        $result = $ucs->create($this->createMock(AttributeMapInterface::class), $object);
    }

    public function testCreateObjectRequestFailedNoDetail()
    {
        $this->expectException(UcsException\RequestFailed::class);
        $response = [
            'result' => [
                [
                    '$dn$' => 'uid=foo,ou=bar',
                    'success' => false,
                ],
            ],
        ];

        $client = $this->getMockClient($response);
        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $object = [
            '$dn$' => 'uid=foo,ou=bar',
            'foo' => 'bar',
        ];

        $result = $ucs->create($this->createMock(AttributeMapInterface::class), $object);
    }

    public function testCreateObjectRequestFailedDetails()
    {
        $this->expectException(UcsException\RequestFailed::class);
        $response = [
            'result' => [
                [
                    '$dn$' => 'uid=foo,ou=bar',
                    'success' => false,
                    'detail' => 'foo',
                ],
            ],
        ];

        $client = $this->getMockClient($response);
        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $object = [
            '$dn$' => 'uid=foo,ou=bar',
            'foo' => 'bar',
        ];

        $result = $ucs->create($this->createMock(AttributeMapInterface::class), $object);
    }

    public function testCreateObjectSimulate()
    {
        $response = [
            'result' => [
                [
                    '$dn$' => 'uid=foo,ou=bar',
                    'success' => true,
                ],
            ],
        ];

        $client = $this->getMockClient($response);
        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $object = [
            '$dn$' => 'uid=foo,ou=bar',
            'foo' => 'bar',
        ];

        $result = $ucs->create($this->createMock(AttributeMapInterface::class), $object, true);
        $this->assertNull($result);
    }

    public function testCreateObjectNoIdentifier()
    {
        $this->expectException(UcsException\NoEntryDn::class);
        $response = [
            'result' => [
                [
                    '$dn$' => 'uid=foo,ou=bar',
                    'success' => true,
                ],
            ],
        ];

        $client = $this->getMockClient($response);
        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $object = [
            'foo' => 'bar',
        ];

        $ucs->create($this->createMock(AttributeMapInterface::class), $object);
    }

    public function testTransformSingleQuery()
    {
        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $this->createMock(Client::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $query = [
            'foo' => 'bar',
        ];

        $expected = [
            'objectProperty' => 'foo',
            'objectPropertyValue' => 'bar',
        ];

        $result = $ucs->transformQuery($query);
        $this->assertSame($expected, $result);
    }

    public function testTransformEmptyQuery()
    {
        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $this->createMock(Client::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $query = [];

        $expected = [
            'objectProperty' => 'None',
        ];

        $result = $ucs->transformQuery($query);
        $this->assertSame($expected, $result);
    }

    public function testTransformFilterAllQuery()
    {
        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $this->createMock(Client::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => [
                'options' => [
                    'filter_all' => json_encode([
                        'foo' => 'bar',
                    ]),
                ],
            ],
        ]);

        $query = [];

        $expected = [
            'objectProperty' => 'foo',
            'objectPropertyValue' => 'bar',
        ];

        $result = $ucs->transformQuery($query);
        $this->assertSame($expected, $result);
    }

    public function testTransformInvalidFilterAllQuery()
    {
        $this->expectException(UcsException\InvalidFilter::class);
        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $this->createMock(Client::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => [
                'options' => [
                    'filter_all' => '{',
                ],
            ],
        ]);

        $result = $ucs->transformQuery([]);
    }

    public function testDeleteObject()
    {
        $response = [
            'result' => [
                [
                    '$dn$' => 'uid=foo,ou=bar',
                    'success' => true,
                ],
            ],
        ];

        $client = $this->getMockClient($response);
        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $object = [
            '$dn$' => 'uid=foo,ou=bar',
            'foo' => 'bar',
        ];

        $result = $ucs->delete($this->createMock(AttributeMapInterface::class), $object, $object);
        $this->assertTrue($result);
    }

    public function testDeleteObjectSimulate()
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->never())->method('__call')
            ->with(
                $this->equalTo('post')
            );
        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $object = [
            '$dn$' => 'uid=foo,ou=bar',
            'foo' => 'bar',
        ];

        $result = $ucs->delete($this->createMock(AttributeMapInterface::class), $object, $object, true);
        $this->assertTrue($result);
    }

    public function testChangeObject()
    {
        $response = [
            'result' => [
                [
                    '$dn$' => 'uid=foo,ou=bar',
                    'success' => true,
                ],
            ],
        ];

        $client = $this->getMockClient($response);
        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $object = [
            '$dn$' => 'uid=foo,ou=bar',
            'foo' => 'foo',
        ];

        $diff = [
            'foo' => 'foo',
        ];

        $result = $ucs->change($this->createMock(AttributeMapInterface::class), $diff, $object, $object);
        $this->assertSame('uid=foo,ou=bar', $result);
    }

    public function testChangeObjectSimulate()
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->never())->method('__call')
            ->with(
                $this->equalTo('post')
            );

        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $object = [
            '$dn$' => 'uid=foo,ou=bar',
            'foo' => 'foo',
        ];

        $diff = [
            'foo' => 'foo',
        ];

        $result = $ucs->change($this->createMock(AttributeMapInterface::class), $diff, $object, $object, true);
        $this->assertNull($result);
    }

    public function testMoveObject()
    {
        $response = [
            'result' => [
                [
                    '$dn$' => 'uid=foo,ou=bar',
                    'success' => true,
                ],
            ],
        ];
        $client = $this->getMockClient($response);
        $client->expects($this->exactly(2))->method('__call');
        $ucs = new Ucs('foo', EndpointInterface::TYPE_DESTINATION, 'users/user', $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $object = [
            '$dn$' => 'uid=foo,ou=bar',
            'foo' => 'foo',
        ];

        $ep_object = [
            '$dn$' => 'uid=foo,ou=foo,ou=foobar',
            'foo' => 'foo',
        ];

        $diff = [];

        $result = $ucs->change($this->createMock(AttributeMapInterface::class), $diff, $object, $ep_object);
        $this->assertSame('uid=foo,ou=bar', $result);
    }

    protected function getMockClient($response = [])
    {
        $body = $this->createMock(StreamInterface::class);
        $body->method('getContents')
            ->willReturn(json_encode($response));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')
            ->willReturn($body);

        $client = $this->createMock(Client::class);
        $client->method('__call')
            ->with(
                $this->equalTo('post')
            )->willReturn($response);

        return $client;
    }
}
