<?php

declare(strict_types=1);

/**
 * tubee
 *
 * @copyright   Copryright (c) 2017-2022 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Testsuite\Unit;

use Micro\Auth\Identity;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Tubee\AccessRole;
use Tubee\AccessRole\Factory as AccessRoleFactory;
use Tubee\AccessRule;
use Tubee\AccessRule\Factory as AccessRuleFactory;
use Tubee\Acl;
use Tubee\Acl\Exception;

class AclTest extends TestCase
{
    public function testDenyIfNoRoles()
    {
        $this->expectException(Exception\NotAllowed::class);

        $role = $this->createMock(AccessRoleFactory::class);
        $role->method('getAll')->will($this->returnCallback(function () {
            if (false) {
                yield 1;
            }
        }));

        $acl = new Acl($role, $this->createMock(AccessRuleFactory::class), $this->createMock(LoggerInterface::class));
        $acl->isAllowed($this->createMock(ServerRequestInterface::class), $this->createMock(Identity::class));
    }

    public function testDenyIfNoRulesMatch()
    {
        $this->expectException(Exception\NotAllowed::class);

        $rule = $this->createMock(AccessRuleFactory::class);
        $rule->method('getAll')->will($this->returnCallback(function () {
            yield new AccessRule([
                'name' => 'allow-foo',
                'data' => [
                    'roles' => ['foo'],
                    'selectors' => ['*'],
                    'verbs' => ['*'],
                    'resources' => ['*'],
                ],
            ]);
        }));

        $acl = new Acl($this->getAllRoleMock(), $rule, $this->createMock(LoggerInterface::class));
        $acl->isAllowed($this->createMock(ServerRequestInterface::class), $this->createMock(Identity::class));
    }

    public function testAllowWildcardRoleAndWildardResource()
    {
        $rule = $this->createMock(AccessRuleFactory::class);
        $rule->method('getAll')->will($this->returnCallback(function () {
            yield new AccessRule([
                'name' => 'allow-all',
                'data' => [
                    'roles' => ['all'],
                    'selectors' => ['*'],
                    'verbs' => ['*'],
                    'resources' => ['*'],
                ],
            ]);
        }));

        $acl = new Acl($this->getAllRoleMock(), $rule, $this->createMock(LoggerInterface::class));
        $this->assertTrue($acl->isAllowed($this->createMock(ServerRequestInterface::class), $this->createMock(Identity::class)));
    }

    public function testDenyNoMatchingVerb()
    {
        $this->expectException(Exception\NotAllowed::class);

        $rule = $this->createMock(AccessRuleFactory::class);
        $rule->method('getAll')->will($this->returnCallback(function () {
            yield new AccessRule([
                'name' => 'allow-post-all',
                'data' => [
                    'roles' => ['all'],
                    'selectors' => ['*'],
                    'verbs' => ['POST'],
                    'resources' => ['*'],
                ],
            ]);
        }));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('GET');

        $acl = new Acl($this->getAllRoleMock(), $rule, $this->createMock(LoggerInterface::class));
        $this->assertTrue($acl->isAllowed($request, $this->createMock(Identity::class)));
    }

    public function testAllowMatchingVerb()
    {
        $rule = $this->createMock(AccessRuleFactory::class);
        $rule->method('getAll')->will($this->returnCallback(function () {
            yield new AccessRule([
                'name' => 'allow-post-all',
                'data' => [
                    'roles' => ['all'],
                    'selectors' => ['*'],
                    'verbs' => ['POST'],
                    'resources' => ['*'],
                ],
            ]);
        }));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');

        $acl = new Acl($this->getAllRoleMock(), $rule, $this->createMock(LoggerInterface::class));
        $this->assertTrue($acl->isAllowed($request, $this->createMock(Identity::class)));
    }

    public function testDenyNoMatchingResource()
    {
        $this->expectException(Exception\NotAllowed::class);

        $rule = $this->createMock(AccessRuleFactory::class);
        $rule->method('getAll')->will($this->returnCallback(function () {
            yield new AccessRule([
                'name' => 'allow-post-all',
                'data' => [
                    'roles' => ['all'],
                    'selectors' => ['foo'],
                    'verbs' => ['*'],
                    'resources' => ['bar'],
                ],
            ]);
        }));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttributes')->willReturn([
            'foo' => 'foo',
        ]);

        $acl = new Acl($this->getAllRoleMock(), $rule, $this->createMock(LoggerInterface::class));
        $this->assertTrue($acl->isAllowed($request, $this->createMock(Identity::class)));
    }

    public function testAllowMatchingResource()
    {
        $rule = $this->createMock(AccessRuleFactory::class);
        $rule->method('getAll')->will($this->returnCallback(function () {
            yield new AccessRule([
                'name' => 'allow-post-all',
                'data' => [
                    'roles' => ['all'],
                    'selectors' => ['foo'],
                    'verbs' => ['*'],
                    'resources' => ['bar'],
                ],
            ]);
        }));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttributes')->willReturn([
            'foo' => 'bar',
        ]);

        $acl = new Acl($this->getAllRoleMock(), $rule, $this->createMock(LoggerInterface::class));
        $this->assertTrue($acl->isAllowed($request, $this->createMock(Identity::class)));
    }

    protected function getAllRoleMock()
    {
        $role = $this->createMock(AccessRoleFactory::class);
        $role->method('getAll')->will($this->returnCallback(function () {
            yield new AccessRole([
                'name' => 'all',
                'data' => [
                    'selectors' => ['*'],
                ],
            ]);
        }));

        return $role;
    }
}
