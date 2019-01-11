<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Unit\Endpoint;

use Dreamscapes\Ldap\Core\Ldap as LdapClient;
use Dreamscapes\Ldap\Core\Result as LdapResult;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\Collection\CollectionInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Endpoint\Exception;
use Tubee\Endpoint\Ldap;
use Tubee\Endpoint\Ldap\Exception as LdapException;
use Tubee\Workflow\Factory as WorkflowFactory;

class LdapTest extends TestCase
{
    public function testSetupDefaultSettings()
    {
        $client = $this->createMock(LdapClient::class);
        $client->expects($this->exactly(1))->method('connect');
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $ldap->setup();
    }

    public function testSetupTls()
    {
        $client = $this->createMock(LdapClient::class);
        $client->expects($this->exactly(1))->method('connect');
        $client->expects($this->exactly(1))->method('startTls');
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $ldap->setLdapOptions([
            'tls' => true,
        ]);

        $ldap->setup();
    }

    public function testSetupSetOptions()
    {
        $client = $this->createMock(LdapClient::class);
        $client->expects($this->exactly(2))->method('setOption');
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $ldap->setLdapOptions([
            'options' => [
                'LDAP_OPT_PROTOCOL_VERSION' => 3,
                'LDAP_OPT_SIZELIMIT' => 10,
            ],
        ]);

        $ldap->setup();
    }

    public function testSetupBind()
    {
        $client = $this->createMock(LdapClient::class);
        $client->expects($this->exactly(1))->method('bind');
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $ldap->setLdapOptions([
            'binddn' => 'foo',
        ]);

        $ldap->setup();
    }

    public function testShutdown()
    {
        $client = $this->createMock(LdapClient::class);
        $client->expects($this->exactly(1))->method('close');
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $ldap->shutdown();
    }

    public function testGetOne()
    {
        $search = $this->createMock(LdapResult::class);
        $search->method('countEntries')->willReturn(1);
        $search->method('getEntries')->willReturn([
            ['uid' => [
                'count' => 1,
                0 => 'foo',
            ]],
        ]);

        $client = $this->createMock(LdapClient::class);
        $client->method('ldapSearch')->willReturn($search);

        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => ['options' => ['filter_one' => '(uid={uid)']],
        ]);

        $result = $ldap->getOne([])->getData();
        $this->assertSame(['uid' => 'foo'], $result);
    }

    public function testGetOneMultipleFound()
    {
        $this->expectException(Exception\ObjectMultipleFound::class);
        $search = $this->createMock(LdapResult::class);
        $search->method('countEntries')->willReturn(2);

        $client = $this->createMock(LdapClient::class);
        $client->method('ldapSearch')->willReturn($search);

        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => ['options' => ['filter_one' => '(uid={uid)']],
        ]);

        $result = $ldap->getOne([])->getData();
    }

    public function testGetOneNotFound()
    {
        $this->expectException(Exception\ObjectNotFound::class);
        $search = $this->createMock(LdapResult::class);
        $search->method('countEntries')->willReturn(0);

        $client = $this->createMock(LdapClient::class);
        $client->method('ldapSearch')->willReturn($search);

        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => ['options' => ['filter_one' => '(uid={uid)']],
        ]);

        $result = $ldap->getOne([])->getData();
    }

    public function testGetDiffNoChange()
    {
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(LdapClient::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $result = $ldap->getDiff($this->createMock(AttributeMapInterface::class), []);
        $this->assertSame([], $result);
    }

    public function testGetDiffReplaceValue()
    {
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(LdapClient::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $diff = [
            'foo' => [
                'attribute' => 'foo',
                'action' => AttributeMapInterface::ACTION_REPLACE,
                'value' => 'bar',
            ],
        ];

        $result = $ldap->getDiff($this->createMock(AttributeMapInterface::class), $diff);
        $expected = [[
            'attrib' => 'foo',
            'modtype' => LDAP_MODIFY_BATCH_REPLACE,
            'values' => ['bar'],
        ]];

        $this->assertSame($expected, $result);
    }

    public function testGetDiffReplaceTwoExistingValue()
    {
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(LdapClient::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
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
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(LdapClient::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
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
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(LdapClient::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
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
    }

    public function testCreateObject()
    {
        $client = $this->createMock(LdapClient::class);
        $client->expects($this->once())->method('add');
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $object = [
            'entrydn' => 'uid=foo,ou=bar',
            'foo' => 'bar',
        ];

        $result = $ldap->create($this->createMock(AttributeMapInterface::class), $object);
        $this->assertSame($object['entrydn'], $result);
    }

    public function testCreateObjectSimulate()
    {
        $client = $this->createMock(LdapClient::class);
        $client->expects($this->never())->method('add');
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $object = [
            'entrydn' => 'uid=foo,ou=bar',
            'foo' => 'bar',
        ];

        $result = $ldap->create($this->createMock(AttributeMapInterface::class), $object, true);
        $this->assertSame($object['entrydn'], $result);
    }

    public function testCreateObjectNoDN()
    {
        $this->expectException(LdapException\NoEntryDn::class);
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(LdapClient::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $object = [
            'foo' => 'bar',
        ];

        $ldap->create($this->createMock(AttributeMapInterface::class), $object);
    }

    public function testTransformSingleAttributeQuery()
    {
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(LdapClient::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));

        $query = [
            'foo' => 'bar',
        ];

        $expected = '(foo=bar)';
        $result = $ldap->transformQuery($query);
        $this->assertSame($expected, $result);
    }

    public function testTransformSimpleQuery()
    {
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(LdapClient::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));

        $query = [
            'foo' => 'bar',
            'bar' => 'foo',
        ];

        $expected = '(&(foo=bar)(bar=foo))';
        $result = $ldap->transformQuery($query);
        $this->assertSame($expected, $result);
    }

    public function testTransformAndQuery()
    {
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(LdapClient::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));

        $query = [
            '$and' => [
                ['foo' => 'bar', 'foobar' => 'foobar'],
                ['bar' => 'foo', 'barf' => 'barf'],
            ],
        ];

        $expected = '(&(&(foo=bar)(foobar=foobar))(&(bar=foo)(barf=barf)))';
        $result = $ldap->transformQuery($query);
        $this->assertSame($expected, $result);
    }

    public function testTransformOrQuery()
    {
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(LdapClient::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));

        $query = [
            '$or' => [
                ['foo' => 'bar'],
                ['bar' => 'foo'],
            ],
        ];

        $expected = '(|(foo=bar)(bar=foo))';
        $result = $ldap->transformQuery($query);
        $this->assertSame($expected, $result);
    }

    public function testTransformOrAndQuery()
    {
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(LdapClient::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));

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

        $expected = '(|(&(foo=bar)(bar=foo))(foobar=bar))';
        $result = $ldap->transformQuery($query);
        $this->assertSame($expected, $result);
    }

    public function testTransformCompareOperatorsQuery()
    {
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(LdapClient::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));

        $query = [
            'foo' => [
                '$gt' => 1,
                '$lte' => 2,
                '$gte' => 3,
                '$lt' => 4,
             ],
        ];

        $expected = '(&(foo>1)(foo<=2)(foo>=3)(foo<4))';
        $result = $ldap->transformQuery($query);
        $this->assertSame($expected, $result);
    }

    public function testTransformOrCompareQuery()
    {
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(LdapClient::class), $this->createMock(CollectionInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));

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

        $expected = '(|(foo>1)(bar<2))';
        $result = $ldap->transformQuery($query);
        $this->assertSame($expected, $result);
    }
}
