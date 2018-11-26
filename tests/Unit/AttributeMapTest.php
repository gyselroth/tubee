<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Unit;

use InvalidArgumentException;
use MongoDB\BSON\Binary;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Tubee\AttributeMap;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\AttributeMap\Exception;

class AttributeMapTest extends TestCase
{
    public function testAttributeEnsureAbsent()
    {
        $map = new AttributeMap([
            'foo' => ['ensure' => AttributeMapInterface::ENSURE_ABSENT],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'bar']);
        $this->assertSame([], $result);
    }

    public function testAttributeEnsureExists()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'ensure' => AttributeMapInterface::ENSURE_EXISTS],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $result);
    }

    public function testAttributeChangeName()
    {
        $map = new AttributeMap([
            'bar' => ['from' => 'foo', 'ensure' => AttributeMapInterface::ENSURE_EXISTS],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'bar']);
        $this->assertSame(['bar' => 'bar'], $result);
    }

    public function testAttributeEnsureMergeNotArray()
    {
        $this->expectException(InvalidArgumentException::class);
        $map = new AttributeMap([
            'string' => ['from' => 'foo', 'type' => 'string', 'ensure' => AttributeMapInterface::ENSURE_MERGE],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'bar']);
    }

    public function testAttributeConvert()
    {
        $map = new AttributeMap([
            'string' => ['from' => 'foo', 'type' => AttributeMapInterface::TYPE_STRING],
            'int' => ['from' => 'foo', 'type' => AttributeMapInterface::TYPE_INT],
            'float' => ['from' => 'foo', 'type' => AttributeMapInterface::TYPE_FLOAT],
            'null' => ['from' => 'foo', 'type' => AttributeMapInterface::TYPE_NULL],
            'bool' => ['from' => 'foo', 'type' => AttributeMapInterface::TYPE_BOOL],
            'array' => ['from' => 'foo', 'type' => AttributeMapInterface::TYPE_ARRAY],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

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
            'foo' => ['from' => 'bar'],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'bar']);
    }

    public function testAttributeNotRequired()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'bar', 'required' => false],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'bar']);
        $this->assertSame([], $result);
    }

    public function testAttributeRequireRegexMatch()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'require_regex' => '#[a-z][A-Z][a-z]#'],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'fOo']);
        $this->assertSame('fOo', $result['foo']);
    }

    public function testAttributeRequireRegexNotMatch()
    {
        $this->expectException(Exception\AttributeRegexNotMatch::class);
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'require_regex' => '#[a-z][A-Z][a-z]#'],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo']);
    }

    public function testAttributeRequireRegexNotMatchNoAttribute()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'bar', 'required' => false, 'require_regex' => '#[a-z][A-Z][a-z]#'],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo']);
        $this->assertSame([], $result);
    }

    public function testAttributeRewriteRegexRuleNoTo()
    {
        $this->expectException(InvalidArgumentException::class);
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'rewrite' => [
                ['match' => '#^foo$#'],
            ]],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo']);
    }

    public function testAttributeRewriteRegexRuleNoMatch()
    {
        $this->expectException(InvalidArgumentException::class);
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'rewrite' => [
                ['to' => 'foo'],
            ]],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo']);
    }

    public function testAttributeRewriteRegexRule()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'rewrite' => [
                ['match' => '#^foo$#', 'to' => 'bar'],
            ]],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo']);
        $this->assertSame('bar', $result['foo']);
    }

    public function testAttributeRewriteRegexRuleFirstMatch()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'rewrite' => [
                ['match' => '#^foo$#', 'to' => 'bar'],
                ['match' => '#^foo$#', 'to' => 'foobar'],
            ]],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo']);
        $this->assertSame('bar', $result['foo']);
    }

    public function testAttributeRewriteRegexRuleFirstMatchLastRule()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'rewrite' => [
                ['match' => '#^fo$#', 'to' => 'bar'],
                ['match' => '#^foo$#', 'to' => 'foobar'],
            ]],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo']);
        $this->assertSame('foobar', $result['foo']);
    }

    public function testAttributeRewriteCompareRule()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'rewrite' => [
                ['regex' => false, 'match' => 'foo', 'to' => 'bar'],
            ]],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo']);
        $this->assertSame('bar', $result['foo']);
    }

    public function testAttributeStaticValue()
    {
        $map = new AttributeMap([
            'foo' => ['value' => 'foobar'],
        ], new ExpressionLanguage(), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo']);
        $this->assertSame('foobar', $result['foo']);
    }

    public function testAttributeScriptDynamicValue()
    {
        $map = new AttributeMap([
            'foo' => ['script' => 'bar'],
        ], new ExpressionLanguage(), $this->createMock(LoggerInterface::class));

        $result = $map->map(['bar' => 'foo']);
        $this->assertSame('foo', $result['foo']);
    }

    public function testAttributeScriptJoinValues()
    {
        $map = new AttributeMap([
            'foo' => ['script' => 'foo~bar'],
        ], new ExpressionLanguage(), $this->createMock(LoggerInterface::class));

        $result = $map->map([
            'foo' => 'foo',
            'bar' => 'bar',
        ]);
        $this->assertSame('foobar', $result['foo']);
    }

    public function testAttributeDynamicValueNotResolvable()
    {
        $this->expectException(Exception\AttributeNotResolvable::class);
        $map = new AttributeMap([
            'foo' => ['script' => 'bar'],
        ], new ExpressionLanguage(), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo']);
        $this->assertSame('foobar', $result['foo']);
    }

    public function testAttributeDynamicValueNotResolvableNotRequired()
    {
        $map = new AttributeMap([
            'foo' => ['script' => 'bar', 'required' => false],
        ], new ExpressionLanguage(), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo']);
        $this->assertSame([], $result);
    }

    public function testArrayAttributeAsString()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'type' => 'string'],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => ['foo', 'bar']]);
        $this->assertSame('foo', $result['foo']);
    }

    public function testUnwindConvertElements()
    {
        $map = new AttributeMap([
            'foo' => [
                'from' => 'foo',
                'type' => 'array',
                'unwind' => [
                    'from' => 'root',
                    'type' => 'int',
                ],
            ],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => ['1', '2']]);
        $this->assertSame([1, 2], $result['foo']);
    }

    public function testUnwindArrayAttributeRequireRegexMatch()
    {
        $map = new AttributeMap([
            'foo' => [
                'from' => 'foo',
                'type' => 'array',
                'unwind' => [
                    'from' => 'root',
                    'require_regex' => '#[a-z][A-Z][a-z]#',
                ],
            ],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => ['fOo', 'fOobar']]);
        $this->assertSame(['fOo', 'fOobar'], $result['foo']);
    }

    public function testUnwindArrayOneAttributeRequireRegexNotMatch()
    {
        $this->expectException(Exception\AttributeRegexNotMatch::class);
        $map = new AttributeMap([
            'foo' => [
                'from' => 'foo',
                'type' => 'array',
                'unwind' => [
                    'from' => 'root',
                    'require_regex' => '#[a-z][A-Z][a-z]#',
                ],
            ],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => ['foo', 'fOo']]);
    }

    public function testUnwindArrayAttributeRewriteRegexRule()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'type' => 'array', 'unwind' => ['from' => 'root', 'rewrite' => [
                ['match' => '#^foo#', 'to' => 'bar'],
            ]]],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => ['foo', 'foobar']]);
        $this->assertSame(['bar', 'barbar'], $result['foo']);
    }

    public function testUnwindArrayAttributeRewriteCompareRule()
    {
        $map = new AttributeMap([
            'foo' => [
                'from' => 'foo',
                'type' => 'array',
                'unwind' => [
                    'from' => 'root',
                    'rewrite' => [
                        ['regex' => false, 'match' => 'foo', 'to' => 'bar'],
                    ],
                ],
            ],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => ['foo', 'foobar']]);
        $this->assertSame(['bar', 'foobar'], $result['foo']);
    }

    public function testBinaryType()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'type' => 'binary'],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $result = $map->map(['foo' => 'foo']);
        $this->assertInstanceOf(Binary::class, $result['foo']);
    }

    public function testGetDiffNoChange()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'foo'],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

        $mapped = ['foo' => 'foo'];
        $existing = ['foo' => 'foo'];
        $result = $map->getDiff($mapped, $existing);
        $this->assertSame([], $result);
    }

    public function testGetDiffUpdateValue()
    {
        $map = new AttributeMap([
            'foo' => ['from' => 'foo', 'ensure' => AttributeMapInterface::ENSURE_LAST],
        ], $this->createMock(ExpressionLanguage::class), $this->createMock(LoggerInterface::class));

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
