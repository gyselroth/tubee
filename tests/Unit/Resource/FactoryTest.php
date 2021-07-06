<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2021 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Unit\Resource;

use Cache\Adapter\Void\VoidCachePool;
use Garden\Schema\ValidationException;
use Helmich\MongoMock\MockDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tubee\Resource\Factory;

class FactoryTest extends TestCase
{
    protected $db;
    protected $factory;

    public function setUp()
    {
        $this->db = new MockDatabase('foobar', [
            'typeMap' => [
                'root' => 'array',
                'document' => 'array',
                'array' => 'array',
            ],
        ]);

        $this->factory = new Factory($this->createMock(LoggerInterface::class), new VoidCachePool());
    }

    public function testGetSchema()
    {
        $this->factory->getSchema('Namespace');
    }

    public function testGetInvalidSchema()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory->getSchema('foo');
    }

    public function testValidate()
    {
        $result = $this->factory->validate([
            'kind' => 'Namespace',
            'name' => 'foo',
        ]);

        $expected = [
            'name' => 'foo',
            'secrets' => [],
            'kind' => 'Namespace',
        ];
        $this->assertSame($expected, $result);
    }

    public function testValidateFilterReadonly()
    {
        $result = $this->factory->validate([
            'kind' => 'Namespace',
            'name' => 'foo',
            'created' => 'foo',
            'changed' => 'foo',
        ]);

        $expected = [
            'name' => 'foo',
            'secrets' => [],
            'kind' => 'Namespace',
        ];
        $this->assertSame($expected, $result);
    }

    public function testValidateFailed()
    {
        $this->expectException(ValidationException::class);
        $this->factory->validate([
            'kind' => 'Namespace',
            'foo' => 'test',
        ]);
    }
}
