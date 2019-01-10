<?php

declare(strict_types=1);

/**
 * tubee.io
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
                'objectProperty' => 'bar',
                'objectPropertyValue' => 'bar',
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
                'objectProperty' => 'bar',
                'objectPropertyValue' => 'bar',
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

    /*
        public function testGetDiffReplaceTwoExistingValue()
        {
            $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(Client::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
            $diff = [
                'foo' => [
                    'action' => AttributeMapInterface::ACTION_REPLACE,
                    'value' => 'bar',
                ],
                'bar' => [
                    'action' => AttributeMapInterface::ACTION_REPLACE,
                    'value' => 'foo',
                ],
            ];
    
            $result = $ldap->getDiff($this->createMock(AttributeMapInterface::class), $diff);
            $expected = [[
                'attrib' => 'foo',
                'modtype' => LDAP_MODIFY_BATCH_REPLACE,
                'values' => ['bar'],
            ], [
                'attrib' => 'bar',
                'modtype' => LDAP_MODIFY_BATCH_REPLACE,
                'values' => ['foo'],
            ]];
    
            $this->assertSame($expected, $result);
        }
    
        public function testGetDiffRemoveValue()
        {
            $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(Client::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
            $diff = [
                'foo' => [
                    'action' => AttributeMapInterface::ACTION_REMOVE,
                ],
            ];
    
            $result = $ldap->getDiff($this->createMock(AttributeMapInterface::class), $diff);
            $expected = [[
                'attrib' => 'foo',
                'modtype' => LDAP_MODIFY_BATCH_REMOVE_ALL,
            ]];
    
            $this->assertSame($expected, $result);
        }
    
        public function testGetDiffAddValue()
        {
            $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(Client::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
            $diff = [
                'foo' => [
                    'action' => AttributeMapInterface::ACTION_ADD,
                    'value' => 'bar',
                ],
            ];
    
            $result = $ldap->getDiff($this->createMock(AttributeMapInterface::class), $diff);
            $expected = [[
                'attrib' => 'foo',
                'modtype' => LDAP_MODIFY_BATCH_ADD,
                'values' => ['bar'],
            ]];
    
            $this->assertSame($expected, $result);
        }*/
}
