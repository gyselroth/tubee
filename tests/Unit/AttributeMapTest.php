<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Unit;

use InvalidArgumentException;
use MongoDB\BSON\Binary;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\AttributeMap\Exception;
use Tubee\V8\Engine as V8Engine;

class AttributeMapTest extends TestCase
{
    public function testAttributeEnsureAbsent()
    {
        $map = new AttributeMap([
            ['name' => 'foo', 'ensure' => AttributeMapInterface::ENSURE_ABSENT],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'bar']);
        $this->assertSame([], $result);
    }

    public function testAttributeEnsureExists()
    {
        $map = new AttributeMap([
            ['name' => 'foo', 'from' => 'foo', 'ensure' => AttributeMapInterface::ENSURE_EXISTS],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $result);
    }

    public function testAttributeChangeName()
    {
        $map = new AttributeMap([
            ['name' => 'bar', 'from' => 'foo', 'ensure' => AttributeMapInterface::ENSURE_EXISTS],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'bar']);
        $this->assertSame(['bar' => 'bar'], $result);
    }

    public function testAttributeEnsureMergeNotArray()
    {
        $this->expectException(InvalidArgumentException::class);
        $map = new AttributeMap([
            ['name' => 'string', 'from' => 'foo', 'type' => 'string', 'ensure' => AttributeMapInterface::ENSURE_MERGE],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'bar']);
    }

    public function testAttributeConvert()
    {
        $map = new AttributeMap([
            ['name' => 'string', 'from' => 'foo', 'type' => AttributeMapInterface::TYPE_STRING],
            ['name' => 'int', 'from' => 'foo', 'type' => AttributeMapInterface::TYPE_INT],
            ['name' => 'float', 'from' => 'foo', 'type' => AttributeMapInterface::TYPE_FLOAT],
            ['name' => 'null', 'from' => 'foo', 'type' => AttributeMapInterface::TYPE_NULL],
            ['name' => 'bool', 'from' => 'foo', 'type' => AttributeMapInterface::TYPE_BOOL],
            ['name' => 'array', 'from' => 'foo', 'type' => AttributeMapInterface::TYPE_ARRAY],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'bar']);
        $this->assertSame('bar', $result['string']);
        $this->assertSame(0, $result['int']);
        $this->assertSame(0.0, $result['float']);
        //$this->assertSame(null, $result['null']);
        $this->assertSame(true, $result['bool']);
        $this->assertSame(['bar'], $result['array']);
    }

    public function testAttributeRequired()
    {
        $this->expectException(Exception\AttributeNotResolvable::class);
        $map = new AttributeMap([
            ['name' => 'foo', 'required' => true, 'from' => 'bar'],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'bar']);
    }

    public function testAttributeNotRequired()
    {
        $map = new AttributeMap([
            ['name' => 'foo', 'from' => 'bar', 'required' => false],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'bar']);
        $this->assertSame([], $result);
    }

    public function testAttributeRequireRegexMatch()
    {
        $map = new AttributeMap([
            ['name' => 'foo', 'from' => 'foo', 'require_regex' => '#[a-z][A-Z][a-z]#'],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'fOo']);
        $this->assertSame('fOo', $result['foo']);
    }

    public function testAttributeRequireRegexNotMatch()
    {
        $this->expectException(Exception\AttributeRegexNotMatch::class);
        $map = new AttributeMap([
            ['name' => 'foo', 'from' => 'foo', 'require_regex' => '#[a-z][A-Z][a-z]#'],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo']);
    }

    public function testAttributeRequireRegexNotMatchNoAttribute()
    {
        $map = new AttributeMap([
            ['name' => 'foo', 'from' => 'bar', 'required' => false, 'require_regex' => '#[a-z][A-Z][a-z]#'],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo']);
        $this->assertSame([], $result);
    }

    public function testAttributeRewriteRegexRule()
    {
        $map = new AttributeMap([
            ['name' => 'foo', 'from' => 'foo', 'rewrite' => [
                ['match' => '#^foo$#', 'to' => 'bar'],
            ]],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo']);
        $this->assertSame('bar', $result['foo']);
    }

    public function testAttributeRewriteRegexRuleFirstMatch()
    {
        $map = new AttributeMap([
            ['name' => 'foo', 'from' => 'foo', 'rewrite' => [
                ['match' => '#^foo$#', 'to' => 'bar'],
                ['match' => '#^foo$#', 'to' => 'foobar'],
            ]],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo']);
        $this->assertSame('bar', $result['foo']);
    }

    public function testAttributeRewriteRegexRuleFirstMatchLastRule()
    {
        $map = new AttributeMap([
            ['name' => 'foo', 'from' => 'foo', 'rewrite' => [
                ['match' => '#^fo$#', 'to' => 'bar'],
                ['match' => '#^foo$#', 'to' => 'foobar'],
            ]],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo']);
        $this->assertSame('foobar', $result['foo']);
    }

    public function testAttributeRewriteCompareRule()
    {
        $map = new AttributeMap([
            ['name' => 'foo', 'from' => 'foo', 'rewrite' => [
                ['from' => 'foo', 'to' => 'bar'],
            ]],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo']);
        $this->assertSame('bar', $result['foo']);
    }

    public function testAttributeStaticValue()
    {
        $map = new AttributeMap([
            ['name' => 'foo',  'value' => 'foobar'],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo']);
        $this->assertSame('foobar', $result['foo']);
    }

    /*public function testAttributeScriptDynamicValue()
    {
        $map = new AttributeMap([
            ['name' => 'foo', 'script' => 'core.result("foobar")'],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $result = $map->map(['bar' => 'foo']);
        $this->assertSame('foobar', $result['foo']);
    }

    public function testAttributeScriptJoinValues()
    {
        $map = new AttributeMap([
            ['name' => 'foo', 'script' => 'core.result(core.object.foo+core.object.bar)'],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $result = $map->map([
            'foo' => 'foo',
            'bar' => 'bar',
        ]);
        $this->assertSame('foobar', $result['foo']);
    }*/

    public function testAttributeDynamicValueNotResolvable()
    {
        $this->expectException(Exception\AttributeNotResolvable::class);
        $map = new AttributeMap([
            ['name' => 'foo', 'required' => true, 'script' => 'foobar'],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo']);
    }

    public function testAttributeDynamicValueNotResolvableNotRequired()
    {
        $map = new AttributeMap([
            ['name' => 'foo', 'script' => 'bar', 'required' => false],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo']);
        $this->assertSame([], $result);
    }

    public function testArrayAttributeAsString()
    {
        $map = new AttributeMap([
            ['name' => 'foo', 'from' => 'foo', 'type' => 'string'],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => ['foo', 'bar']]);
        $this->assertSame('foo', $result['foo']);
    }

    public function testUnwindConvertElements()
    {
        $map = new AttributeMap([
            [
                'name' => 'foo',
                'from' => 'foo',
                'type' => 'array',
                'unwind' => [
                    'from' => 'root',
                    'type' => 'int',
                ],
            ],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => ['1', '2']]);
        $this->assertSame([1, 2], $result['foo']);
    }

    public function testUnwindArrayAttributeRequireRegexMatch()
    {
        $map = new AttributeMap([
            [
                'name' => 'foo',
                'from' => 'foo',
                'type' => 'array',
                'unwind' => [
                    'from' => 'root',
                    'require_regex' => '#[a-z][A-Z][a-z]#',
                ],
            ],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => ['fOo', 'fOobar']]);
        $this->assertSame(['fOo', 'fOobar'], $result['foo']);
    }

    public function testUnwindArrayOneAttributeRequireRegexNotMatch()
    {
        $this->expectException(Exception\AttributeRegexNotMatch::class);
        $map = new AttributeMap([
            [
                'name' => 'foo',
                'from' => 'foo',
                'type' => 'array',
                'unwind' => [
                    'from' => 'root',
                    'require_regex' => '#[a-z][A-Z][a-z]#',
                ],
            ],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => ['foo', 'fOo']]);
    }

    public function testUnwindArrayAttributeRewriteRegexRule()
    {
        $map = new AttributeMap([
            ['name' => 'foo', 'from' => 'foo', 'type' => 'array', 'unwind' => ['from' => 'root', 'rewrite' => [
                ['match' => '#^foo#', 'to' => 'bar'],
            ]]],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => ['foo', 'foobar']]);
        $this->assertSame(['bar', 'barbar'], $result['foo']);
    }

    public function testUnwindArrayAttributeRewriteCompareRule()
    {
        $map = new AttributeMap([
            [
                'name' => 'foo',
                'from' => 'foo',
                'type' => 'array',
                'unwind' => [
                    'from' => 'root',
                    'rewrite' => [
                        ['from' => 'foo', 'to' => 'bar'],
                    ],
                ],
            ],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => ['foo', 'foobar']]);
        $this->assertSame(['bar', 'foobar'], $result['foo']);
    }

    public function testBinaryType()
    {
        $map = new AttributeMap([
            ['name' => 'foo', 'from' => 'foo', 'type' => 'binary'],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo']);
        $this->assertInstanceOf(Binary::class, $result['foo']);
    }

    public function testGetDiffNoChange()
    {
        $map = new AttributeMap([
            ['name' => 'foo', 'ensure' => 'last', 'from' => 'foo'],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $mapped = ['foo' => 'foo'];
        $existing = ['foo' => 'foo'];
        $result = $map->getDiff($mapped, $existing);
        $this->assertSame([], $result);
    }

    public function testGetDiffUpdateValue()
    {
        $map = new AttributeMap([
            ['name' => 'foo', 'from' => 'foo', 'ensure' => AttributeMapInterface::ENSURE_LAST],
        ], new V8Engine($this->createMock(LoggerInterface::class)), $this->createMock(LoggerInterface::class));

        $mapped = ['foo' => 'foo'];
        $existing = ['foo' => 'bar'];
        $result = $map->getDiff($mapped, $existing);

        $expected = [
            'foo' => [
                'action' => AttributeMapInterface::ACTION_REPLACE,
                'value' => 'foo',
            ],
        ];

        $this->assertSame($expected, $result);
    }
}
