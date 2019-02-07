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
use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\Balloon;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Endpoint\Exception;
use Tubee\Workflow\Factory as WorkflowFactory;

class BalloonTest extends TestCase
{
    public function testGetOne()
    {
        $response = [
            'data' => [
                [
                    'id' => (string) new ObjectId(),
                    'foo' => 'foo',
                ],
            ],
        ];

        $client = $this->getMockClient($response);

        $ucs = new Balloon('foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => ['options' => ['filter_one' => json_encode([
                'foo' => 'foo',
            ])]],
        ]);

        $result = $ucs->getOne([])->getData();
        $this->assertSame('foo', $result['foo']);
    }

    public function testGetOneMultipleFound()
    {
        $this->expectException(Exception\ObjectMultipleFound::class);
        $response = [
            'data' => [
                [
                    'id' => (string) new ObjectId(),
                    'foo' => 'foo',
                ],
                [
                    'id' => (string) new ObjectId(),
                    'foo' => 'bar',
                ],
            ],
        ];

        $client = $this->getMockClient($response);

        $ucs = new Balloon('foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => ['options' => ['filter_one' => json_encode([
                'data' => 'foo',
            ])]],
        ]);

        $ucs->getOne([]);
    }

    public function testGetOneNotFound()
    {
        $this->expectException(Exception\ObjectNotFound::class);
        $response = [
            'data' => [],
        ];

        $client = $this->getMockClient($response);

        $ucs = new Balloon('foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => ['options' => ['filter_one' => json_encode([
                'foo' => 'bar',
            ])]],
        ]);

        $ucs->getOne([]);
    }

    public function testTransformSingleQuery()
    {
        $ucs = new Balloon('foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(Client::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $query = [
            'foo' => 'foo',
            'bar' => 'bar',
        ];

        $expected = json_encode([
            'foo' => 'foo',
            'bar' => 'bar',
        ]);

        $result = $ucs->transformQuery($query);
        $this->assertSame($expected, $result);
    }

    public function testTransformEmptyQuery()
    {
        $ucs = new Balloon('foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(Client::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $query = [];
        $result = $ucs->transformQuery($query);
        $this->assertSame(null, $result);
    }

    public function testTransformFilterCombineAllAndRequestQuery()
    {
        $ucs = new Balloon('foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(Client::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => [
                'options' => [
                    'filter_all' => json_encode([
                        'foo' => 'foo',
                        'bar' => 'foo',
                    ]),
                ],
            ],
        ]);

        $query = [
            'barfoo' => 'foo',
        ];

        $expected = json_encode([
            'foo' => 'foo',
            'bar' => 'foo',
        ]);

        $expected = '{"$and":['.$expected.', '.json_encode($query).']}';

        $result = $ucs->transformQuery($query);
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
                $this->equalTo('get')
            )->willReturn($response);

        return $client;
    }
}
