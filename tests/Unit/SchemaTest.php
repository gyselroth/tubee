<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tubee\Schema;
use Tubee\Schema\Exception;

class SchemaTest extends TestCase
{
    public function testValidSchema()
    {
        $schema = new Schema([
            'foo' => ['required' => true],
        ], $this->createMock(LoggerInterface::class));

        $result = $schema->validate(['foo' => 'bar']);
        $this->assertTrue($result);
    }

    public function testAttributeRequiredNotFound()
    {
        $this->expectException(Exception\AttributeNotFound::class);
        $schema = new Schema([
            'foo' => ['required' => true],
        ], $this->createMock(LoggerInterface::class));

        $schema->validate(['bar' => 'bar']);
    }

    public function testAttributeRequireRegexMatch()
    {
        $schema = new Schema([
            'foo' => ['required' => true, 'require_regex' => '#bar#'],
        ], $this->createMock(LoggerInterface::class));

        $result = $schema->validate(['foo' => 'bar']);
        $this->assertTrue($result);
    }

    public function testAttributeRequireRegexNotMatch()
    {
        $this->expectException(Exception\AttributeRegexNotMatch::class);
        $schema = new Schema([
            'foo' => ['require_regex' => '#[a-z][A-Z][a-z]#'],
        ], $this->createMock(LoggerInterface::class));

        $schema->validate(['foo' => 'foo']);
    }

    public function testAttributeTypeMatch()
    {
        $schema = new Schema([
            'foo' => ['type' => 'string'],
        ], $this->createMock(LoggerInterface::class));

        $result = $schema->validate(['foo' => 'foo']);
        $this->assertTrue($result);
    }

    public function testAttributeTypeNotMatch()
    {
        $this->expectException(Exception\AttributeInvalidType::class);
        $schema = new Schema([
            'foo' => ['type' => 'int'],
        ], $this->createMock(LoggerInterface::class));

        $schema->validate(['foo' => 'foo']);
    }
}
