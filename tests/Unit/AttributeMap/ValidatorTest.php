<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2019 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Unit\AttributeMap;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\AttributeMap\Validator;

class ValidatorTest extends TestCase
{
    public function testAttributeInvalidMap()
    {
        $this->expectException(InvalidArgumentException::class);
        $resource = ['foo'];
        Validator::validate($resource);
    }

    public function testAttributeInvalidAttributeName()
    {
        $this->expectException(InvalidArgumentException::class);
        $resource = [['foo' => 'bar']];
        Validator::validate($resource);
    }

    public function testAttributeInvalidType()
    {
        $this->expectException(InvalidArgumentException::class);
        $resource = [['name' => 'foo', 'type' => 'foo']];
        Validator::validate($resource);
    }

    public function testValidAttributeMap()
    {
        $resource = [[
            'name' => 'foo',
            'type' => 'string',
            'required' => false,
            'require_regex' => '#.*#',
            'from' => 'bar',
            'script' => null,
        ]];

        $expected = [[
            'name' => 'foo',
            'type' => 'string',
            'name' => 'foo',
            'required' => false,
            'require_regex' => '#.*#',
            'from' => 'bar',
            'script' => null,
            'unwind' => null,
            'value' => null,
            'rewrite' => [],
            'map' => null,
            'ensure' => AttributeMapInterface::ENSURE_LAST,
        ]];

        $this->assertEquals(Validator::validate($resource), $expected);
    }

    public function testAttributeInvalidEnsure()
    {
        $this->expectException(InvalidArgumentException::class);
        $resource = [['name' => 'foo', 'ensure' => 'foo']];
        Validator::validate($resource);
    }

    public function testAttributeInvalidOption()
    {
        $this->expectException(InvalidArgumentException::class);
        $resource = [['name' => 'foo', 'foo' => 'foo']];
        Validator::validate($resource);
    }

    public function testAttributeInvalidRequire()
    {
        $this->expectException(InvalidArgumentException::class);
        $resource = [['name' => 'foo', 'require' => 'foo']];
        Validator::validate($resource);
    }
}
