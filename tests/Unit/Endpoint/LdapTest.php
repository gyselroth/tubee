<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Unit\Endpoint;

use Dreamscapes\Ldap\Core\Ldap as LdapClient;
use Dreamscapes\Ldap\Core\Result as LdapResult;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\DataType\DataTypeInterface;
use Tubee\Endpoint\EndpointInterface;
use Tubee\Endpoint\Exception;
use Tubee\Endpoint\Ldap;
use Tubee\Workflow\Factory as WorkflowFactory;

class LdapTest extends TestCase
{
    public function testSetupDefaultSettings()
    {
        $client = $this->createMock(LdapClient::class);
        $client->expects($this->exactly(1))->method('connect');
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(DataTypeInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $ldap->setup();
    }

    public function testSetupTls()
    {
        $client = $this->createMock(LdapClient::class);
        $client->expects($this->exactly(1))->method('connect');
        $client->expects($this->exactly(1))->method('startTls');
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(DataTypeInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $ldap->setLdapOptions([
            'tls' => true,
        ]);

        $ldap->setup();
    }

    public function testSetupSetOptions()
    {
        $client = $this->createMock(LdapClient::class);
        $client->expects($this->exactly(2))->method('setOption');
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(DataTypeInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
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
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(DataTypeInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $ldap->setLdapOptions([
            'binddn' => 'foo',
        ]);

        $ldap->setup();
    }

    public function testShutdown()
    {
        $client = $this->createMock(LdapClient::class);
        $client->expects($this->exactly(1))->method('close');
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(DataTypeInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
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

        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(DataTypeInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
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

        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(DataTypeInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
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

        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(DataTypeInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => ['options' => ['filter_one' => '(uid={uid)']],
        ]);

        $result = $ldap->getOne([])->getData();
    }

    public function testObjectExists()
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

        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(DataTypeInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => ['options' => ['filter_one' => '(uid={uid)']],
        ]);

        $this->assertTrue($ldap->exists([]));
    }

    public function testObjectExistsIfMultipleFound()
    {
        $search = $this->createMock(LdapResult::class);
        $search->method('countEntries')->willReturn(2);
        $client = $this->createMock(LdapClient::class);
        $client->method('ldapSearch')->willReturn($search);

        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(DataTypeInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => ['options' => ['filter_one' => '(uid={uid)']],
        ]);

        $this->assertTrue($ldap->exists([]));
    }

    public function testObjectNotExistsIfNotFound()
    {
        $search = $this->createMock(LdapResult::class);
        $search->method('countEntries')->willReturn(0);
        $client = $this->createMock(LdapClient::class);
        $client->method('ldapSearch')->willReturn($search);

        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $client, $this->createMock(DataTypeInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class), [
            'data' => ['options' => ['filter_one' => '(uid={uid)']],
        ]);

        $this->assertFalse($ldap->exists([]));
    }

    public function testGetDiffNoChange()
    {
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(LdapClient::class), $this->createMock(DataTypeInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
        $result = $ldap->getDiff($this->createMock(AttributeMapInterface::class), []);
        $this->assertSame([], $result);
    }

    public function testGetDiffReplaceValue()
    {
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(LdapClient::class), $this->createMock(DataTypeInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
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
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(LdapClient::class), $this->createMock(DataTypeInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
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
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(LdapClient::class), $this->createMock(DataTypeInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
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
        $ldap = new Ldap('foo', EndpointInterface::TYPE_DESTINATION, $this->createMock(LdapClient::class), $this->createMock(DataTypeInterface::class), $this->createMock(WorkflowFactory::class), $this->createMock(LoggerInterface::class));
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
}
