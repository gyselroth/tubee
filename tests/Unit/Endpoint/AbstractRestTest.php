<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2021 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Unit\Endpoint;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\AbstractRest;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Endpoint\Exception;
use Tubee\Endpoint\Rest\Exception as RestException;
use Tubee\Workflow\Factory as WorkflowFactory;

class AbstractRestTest extends TestCase
{
    public function testSetupDefaultSettings()
    {
        $client = $this->getMockClient('get', []);
        $ep = $this->getMockForAbstractClass(AbstractRest::class, [
            'foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class),
        ]);

        $ep->setup();
    }

    /*
        public function testSetupAuthOAuth2ClientCredentials()
        {
            $body = $this->createMock(StreamInterface::class);
            $body->method('getContents')
                ->willReturn(json_encode(['access_token' => 'foo']));

            $response = $this->createMock(ResponseInterface::class);
            $response->method('getBody')
                ->willReturn($body);

            $client = $this->createMock(Client::class);
            $client->expects($this->at(0))->method('__call')
                ->with(
                    $this->equalTo('post')
                )->willReturn($response);

            $client->expects($this->at(1))->method('__call')
                ->with(
                    $this->equalTo('get')
                )->willReturn($response);

            $ep = $this->getMockForAbstractClass(AbstractRest::class, [
                'foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
                    'data' => [
                        'resource' => [
                            'auth' => 'oauth2',
                            'oauth2' => [
                                'token_endpoint' => 'foo',
                                'client_id' => 'foo',
                                'client_secret' => 'foo',
                                'scope' => 'test',
                            ],
                        ],
                    ],
                ],
            ]);

            $ep->setup();
        }

        public function testSetupAuthOAuth2ClientCredentialsNoToken()
        {
            $this->expectException(RestException\AccessTokenNotAvailable::class);
            $client = $this->getMockClient('post', [
                'foo' => 'foo',
            ]);

            $ep = $this->getMockForAbstractClass(AbstractRest::class, [
                'foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
                    'data' => [
                        'resource' => [
                            'auth' => 'oauth2',
                            'oauth2' => [
                                'token_endpoint' => 'foo',
                                'client_id' => 'foo',
                                'client_secret' => 'foo',
                                'scope' => 'test',
                            ],
                        ],
                    ],
                ],
            ]);

            $ep->setup();
        }
     */
    /*
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
                'entrydn' => 'uid=foo,ou=bar',
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
    */
    protected function getMockClient($verb = 'get', $response = [])
    {
        $body = $this->createMock(StreamInterface::class);
        $body->method('getContents')
            ->willReturn(json_encode($response));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')
            ->willReturn($body);

        $client = $this->createMock(Client::class);
        $client->expects($this->once())->method('__call')
            ->with(
                $this->equalTo($verb)
            )->willReturn($response);

        return $client;
    }
}
