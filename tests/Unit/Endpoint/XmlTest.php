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
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Endpoint\Exception;
use Tubee\Endpoint\Xml;
use Tubee\Storage\StorageInterface;
use Tubee\Workflow\Factory as WorkflowFactory;

class XmlTest extends TestCase
{
    public function testSetupDestinationEndpoint()
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->expects($this->once())
            ->method('openWriteStream')
            ->willReturn(fopen('data://text/plain;base64,'.base64_encode('<root></root>'), 'rw'));

        $xml = new Xml('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $storage, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $xml->setup();
    }

    public function testSetupSourceEndpoint()
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->expects($this->once())
            ->method('openReadStreams')
            ->will($this->returnCallback(function () {
                yield fopen('data://text/plain;base64,'.base64_encode('<root></root>'), 'r');
                yield fopen('data://text/plain;base64,'.base64_encode('<root></root>'), 'r');
            }));

        $xml = new Xml('foo', EndpointInterface::TYPE_SOURCE, 'foobar', $storage, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $xml->setup();
    }

    public function testShutdownDestinationEndpoint()
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->expects($this->once())
            ->method('openWriteStream')
            ->willReturn(fopen('data://text/plain;base64,'.base64_encode('<root></root>'), 'rw'));
        $storage
            ->expects($this->once())
            ->method('syncWriteStream');

        $xml = new Xml('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $storage, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $xml->setup();
        $xml->shutdown();
    }

    public function testShutdownDestinationEndpointWriteFailed()
    {
        $this->expectException(Exception\WriteOperationFailed::class);
        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->expects($this->once())
            ->method('openWriteStream')
            ->willReturn(fopen('php://temp/maxmemory:1', 'r'));

        $xml = new Xml('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $storage, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $xml->setup();
        $xml->shutdown();
    }

    public function testShutdownSourceEndpoint()
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects($this->never())->method('syncWriteStream');
        $xml = new Xml('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $storage, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $xml->shutdown();
    }

    public function testGetOne()
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->expects($this->once())
            ->method('openReadStreams')
            ->will($this->returnCallback(function () {
                yield fopen('data://text/plain;base64,'.base64_encode('<root><node><foo>bar</foo></node></root>'), 'r');
            }));

        $xml = new Xml('foo', EndpointInterface::TYPE_SOURCE, 'foobar', $storage, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => ['options' => [
                'filter_one' => json_encode(['foo' => '{foo}']),
            ]],
        ]);

        $xml->setup();
        $result = $xml->getOne(['foo' => 'bar'])->getData();
        $this->assertSame(['foo' => 'bar'], $result);
    }

    public function testGetOneNodeAttributes()
    {
        $stream = fopen('php://memory', 'rw');
        fwrite($stream, '<root><node bar="foo"><bar>foo</bar></node></root>');
        rewind($stream);

        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->expects($this->once())
            ->method('openReadStreams')
            ->will($this->returnCallback(function () use ($stream) {
                yield $stream;
            }));

        $xml = new Xml('foo', EndpointInterface::TYPE_SOURCE, 'foobar', $storage, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => ['options' => [
                'filter_one' => json_encode(['bar' => '{foo}']),
            ]],
        ]);

        $xml->setup();
        $result = $xml->getOne(['foo' => 'foo'])->getData();
        $this->assertSame(['@attributes' => ['bar' => 'foo'], 'bar' => 'foo'], $result);
    }

    public function testGetOneMultipleFound()
    {
        $this->expectException(Exception\ObjectMultipleFound::class);
        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->expects($this->once())
            ->method('openReadStreams')
            ->will($this->returnCallback(function () {
                yield fopen('data://text/plain;base64,'.base64_encode('<root><node><foo>bar</foo></node><node><foo>bar</foo></node></root>'), 'r');
            }));

        $xml = new Xml('foo', EndpointInterface::TYPE_SOURCE, 'foobar', $storage, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => ['options' => [
                'filter_one' => json_encode(['foo' => '{foo}']),
            ]],
        ]);

        $xml->setup();
        $result = $xml->getOne(['foo' => 'bar']);
    }

    public function testGetOneNotFound()
    {
        $this->expectException(Exception\ObjectNotFound::class);
        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->expects($this->once())
            ->method('openReadStreams')
            ->will($this->returnCallback(function () {
                yield fopen('data://text/plain;base64,'.base64_encode('<root></root>'), 'r');
            }));

        $xml = new Xml('foo', EndpointInterface::TYPE_SOURCE, 'foobar', $storage, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => ['options' => [
                'filter_one' => json_encode(['foo' => '{foo}']),
            ]],
        ]);

        $xml->setup();
        $result = $xml->getOne(['foo' => 'bar']);
    }

    public function testGetDiffNoAction()
    {
        $xml = new Xml('foo', EndpointInterface::TYPE_SOURCE, 'foobar', $this->createMock(StorageInterface::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $result = $xml->getDiff($this->createMock(AttributeMapInterface::class), [
            'foo' => 'bar',
        ]);

        $this->assertSame(['foo' => 'bar'], $result);
    }

    public function testCreateObject()
    {
        $stream = fopen('php://memory', 'rw');
        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->expects($this->once())
            ->method('openWriteStream')
            ->willReturn($stream);

        $xml = new Xml('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $storage, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $xml->setup();
        $xml->create($this->createMock(AttributeMapInterface::class), [
            'foo' => 'bar',
        ]);
        $xml->shutdown();

        rewind($stream);
        $result = stream_get_contents($stream);
        $expected = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<data>
  <row>
    <foo>bar</foo>
  </row>
</data>\n
EOT;

        $this->assertSame($expected, $result);
    }

    public function testCreateObjectWithExistingData()
    {
        $stream = fopen('php://memory', 'rw');
        fwrite($stream, '<root><row><bar>foo</bar></row></root>');
        rewind($stream);

        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->expects($this->once())
            ->method('openWriteStream')
            ->willReturn($stream);

        $xml = new Xml('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $storage, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $xml->setup();
        $xml->create($this->createMock(AttributeMapInterface::class), [
            'foo' => 'bar',
        ]);
        $xml->shutdown();

        rewind($stream);
        $result = stream_get_contents($stream);
        $expected = <<<EOT
<?xml version="1.0"?>
<root>
  <row>
    <bar>foo</bar>
  </row>
  <row>
    <foo>bar</foo>
  </row>
</root>\n
EOT;

        $this->assertSame($expected, $result);
    }

    public function testCreateObjectArrayValue()
    {
        $stream = fopen('php://memory', 'rw');
        fwrite($stream, '<root></root>');

        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->expects($this->once())
            ->method('openWriteStream')
            ->willReturn($stream);

        $xml = new Xml('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $storage, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $xml->setup();
        $xml->create($this->createMock(AttributeMapInterface::class), [
            'foo' => 'bar',
            'bar' => [
                'foo',
                'foobar',
            ],
        ]);
        $xml->shutdown();

        rewind($stream);
        $result = stream_get_contents($stream);
        $expected = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<data>
  <row>
    <foo>bar</foo>
    <bar>
      <bar>foo</bar>
      <bar>foobar</bar>
    </bar>
  </row>
</data>\n
EOT;

        $this->assertSame($expected, $result);
    }

    public function testCreateObjectDifferentNodeNames()
    {
        $stream = fopen('php://memory', 'rw');
        fwrite($stream, '<root></root>');

        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->expects($this->once())
            ->method('openWriteStream')
            ->willReturn($stream);

        $xml = new Xml('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $storage, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => [
                'resource' => [
                    'node_name' => 'bar',
                    'root_name' => 'foo',
                    'pretty' => false,
                    'preserve_whitespace' => true,
                ],
            ],
        ]);
        $xml->setup();
        $xml->create($this->createMock(AttributeMapInterface::class), [
            'foo' => 'bar',
        ]);
        $xml->shutdown();

        rewind($stream);
        $result = stream_get_contents($stream);
        $expected = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<foo><bar><foo>bar</foo></bar></foo>\n
EOT;

        $this->assertSame($expected, $result);
    }

    public function testInvalidXmlResourceArgument()
    {
        $this->expectException(\InvalidArgumentException::class);
        $xml = new Xml('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $this->createMock(StorageInterface::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => [
                'resource' => [
                    'foo' => 'bar',
                ],
            ],
        ]);
    }

    public function testTransformSingleAttributeQuery()
    {
        $xml = new Xml('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $this->createMock(StorageInterface::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));

        $query = [
            'foo' => 'bar',
        ];

        $expected = "//*[(foo='bar')]";
        $result = $xml->transformQuery($query);
        $this->assertSame($expected, $result);
    }

    public function testTransformQueryAndFilterAll()
    {
        $xml = new Xml('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $this->createMock(StorageInterface::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => [
                'options' => [
                    'filter_all' => json_encode(['bar' => 'foo']),
                ],
            ],
        ]);

        $query = [
            'foo' => 'bar',
        ];

        $expected = "//*[((bar='foo') and (foo='bar'))]";
        $result = $xml->transformQuery($query);
        $this->assertSame($expected, $result);
    }

    public function testTransformQueryAndFilterAllSubNode()
    {
        $xml = new Xml('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $this->createMock(StorageInterface::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => [
                'options' => [
                    'filter_all' => json_encode(['bar' => 'bar']),
                ],
            ],
        ]);

        $query = [
            'foo' => 'bar',
        ];

        $expected = "//*[((bar='bar') and (foo='bar'))]";
        $result = $xml->transformQuery($query);
        $this->assertSame($expected, $result);
    }

    public function testTransformAndQuery()
    {
        $xml = new Xml('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $this->createMock(StorageInterface::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));

        $query = [
            '$and' => [
                ['foo' => 'bar', 'foobar' => 'foobar'],
                ['bar' => 'foo', 'barf' => 'barf'],
            ],
        ];

        $expected = "//*[(((foo='bar') and (foobar='foobar')) and ((bar='foo') and (barf='barf')))]";
        $result = $xml->transformQuery($query);
        $this->assertSame($expected, $result);
    }

    public function testTransformOrQuery()
    {
        $xml = new Xml('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $this->createMock(StorageInterface::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));

        $query = [
            '$or' => [
                ['foo' => 'bar'],
                ['bar' => 'foo'],
            ],
        ];

        $expected = "//*[((foo='bar') or (bar='foo'))]";
        $result = $xml->transformQuery($query);
        $this->assertSame($expected, $result);
    }

    public function testTransformOrAndQuery()
    {
        $xml = new Xml('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $this->createMock(StorageInterface::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));

        $query = [
            '$or' => [
                [
                    '$and' => [
                        ['foo' => 'bar'],
                        ['bar' => 'foo'],
                    ],
                ],
                [
                    'foobar' => 'bar',
                ],
            ],
        ];

        $expected = "//*[(((foo='bar') and (bar='foo')) or (foobar='bar'))]";
        $result = $xml->transformQuery($query);
        $this->assertSame($expected, $result);
    }

    public function testTransformCompareOperatorsQuery()
    {
        $xml = new Xml('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $this->createMock(StorageInterface::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));

        $query = [
            'foo' => [
                '$gt' => 1,
                '$lte' => 2,
                '$gte' => 3,
                '$lt' => 4,
                '$ne' => 5,
             ],
        ];

        $expected = "//*[((foo>'1') and (foo<='2') and (foo>='3') and (foo<'4') and (foo!='5'))]";
        $result = $xml->transformQuery($query);
        $this->assertSame($expected, $result);
    }

    public function testTransformOrCompareQuery()
    {
        $xml = new Xml('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $this->createMock(StorageInterface::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));

        $query = [
            '$or' => [
                [
                    'foo' => ['$gt' => 1],
                ],
                [
                    'bar' => ['$lt' => 2],
                ],
            ],
        ];

        $expected = "//*[((foo>'1') or (bar<'2'))]";
        $result = $xml->transformQuery($query);
        $this->assertSame($expected, $result);
    }

    public function testDeleteObject()
    {
        $stream = fopen('php://memory', 'rw');
        fwrite($stream, '<root><row><bar>foo</bar></row></root>');
        rewind($stream);

        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->expects($this->once())
            ->method('openWriteStream')
            ->willReturn($stream);

        $xml = new Xml('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $storage, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => [
                'options' => [
                    'filter_one' => json_encode(['bar' => '{foo}']),
                ],
            ],
        ]);
        $xml->setup();
        $xml->delete($this->createMock(AttributeMapInterface::class), [
            'foo' => 'foo',
        ], [
            'bar' => 'foo',
        ]);

        $xml->shutdown();

        rewind($stream);
        $result = stream_get_contents($stream);
        $expected = <<<EOT
<?xml version="1.0"?>
<root/>\n
EOT;

        $this->assertSame($expected, $result);
    }

    public function testChangeObject()
    {
        $stream = fopen('php://memory', 'rw');
        fwrite($stream, '<root><row><bar>foo</bar><foobar>foo</foobar></row></root>');
        rewind($stream);

        $storage = $this->createMock(StorageInterface::class);
        $storage
            ->expects($this->once())
            ->method('openWriteStream')
            ->willReturn($stream);

        $xml = new Xml('foo', EndpointInterface::TYPE_DESTINATION, 'foobar', $storage, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => [
                'options' => [
                    'filter_one' => json_encode(['bar' => '{foo}']),
                ],
            ],
        ]);
        $xml->setup();

        $diff = [
            'bar' => [
                'action' => AttributeMapInterface::ACTION_REPLACE,
                'value' => 'bar',
            ],
            'foo' => [
                'action' => AttributeMapInterface::ACTION_ADD,
                'value' => 'bar',
            ],
            'foobar' => [
                'action' => AttributeMapInterface::ACTION_REMOVE,
            ],
        ];

        $xml->change($this->createMock(AttributeMapInterface::class), $diff, [
            'foo' => 'foo',
        ], [
            'bar' => 'foo',
            'foobar' => 'foobar',
        ]);

        $xml->shutdown();

        rewind($stream);
        $result = stream_get_contents($stream);
        $expected = <<<EOT
<?xml version="1.0"?>
<root>
  <row>
    <bar>bar</bar>
    <foo>bar</foo>
  </row>
</root>\n
EOT;

        $this->assertSame($expected, $result);
    }
}
