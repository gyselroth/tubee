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
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\AttributeMap\Validator;

class ValidatorTest extends TestCase
{
    public function testAttributeInvalidMap()
    {
        $this->expectException(InvalidArgumentException::class);
        $resource = ['foo'];
        Validator::validate($resource, $this->createMock(ExpressionLanguage::class));
    }

    public function testAttributeInvalidAttributeName()
    {
        $this->expectException(InvalidArgumentException::class);
        $resource = [0 => 'foo'];
        Validator::validate($resource, $this->createMock(ExpressionLanguage::class));
    }

    public function testAttributeInvalidType()
    {
        $this->expectException(InvalidArgumentException::class);
        $resource = ['foo' => ['type' => 'foo']];
        Validator::validate($resource, $this->createMock(ExpressionLanguage::class));
    }

    public function testValidAttributeMap()
    {
        $resource = ['foo' => [
            'type' => 'string',
            'required' => false,
            'require_regex' => '#.*#',
            'from' => 'bar',
            'script' => null,
        ]];

        $expected = ['foo' => [
            'type' => 'string',
            'required' => false,
            'require_regex' => '#.*#',
            'from' => 'bar',
            'script' => null,
            'value' => null,
            'rewrite' => [],
            'map' => [],
            'ensure' => AttributeMapInterface::ENSURE_LAST,
        ]];

        $this->assertEquals(Validator::validate($resource, $this->createMock(ExpressionLanguage::class)), $expected);
    }

    public function testAttributeInvalidEnsure()
    {
        $this->expectException(InvalidArgumentException::class);
        $resource = ['foo' => ['ensure' => 'foo']];
        Validator::validate($resource, $this->createMock(ExpressionLanguage::class));
    }

    public function testAttributeInvalidOption()
    {
        $this->expectException(InvalidArgumentException::class);
        $resource = ['foo' => ['foo' => 'foo']];
        Validator::validate($resource, $this->createMock(ExpressionLanguage::class));
    }

    public function testAttributeInvalidRequire()
    {
        $this->expectException(InvalidArgumentException::class);
        $resource = ['foo' => ['require' => 'foo']];
        Validator::validate($resource, $this->createMock(ExpressionLanguage::class));
    }
}
