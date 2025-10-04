<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Tests\Unit;

use DFiks\UnPerm\Support\PermissionResult;
use DFiks\UnPerm\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PermissionResultTest extends TestCase
{
    public function testAllowedReturnsTrue(): void
    {
        $result = new PermissionResult(true, 'test.action');

        $this->assertTrue($result->allowed());
        $this->assertFalse($result->denied());
    }

    public function testDeniedReturnsTrue(): void
    {
        $result = new PermissionResult(false, 'test.action');

        $this->assertFalse($result->allowed());
        $this->assertTrue($result->denied());
    }

    public function testThrowDeniedThrowsWhenDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Unauthorized action: test.action');

        $result = new PermissionResult(false, 'test.action');
        $result->throwDenied();
    }

    public function testThrowDeniedDoesNotThrowWhenAllowed(): void
    {
        $result = new PermissionResult(true, 'test.action');
        $returned = $result->throwDenied();

        $this->assertSame($result, $returned);
    }

    public function testThrowAllowedThrowsWhenAllowed(): void
    {
        $this->expectException(AccessDeniedHttpException::class);

        $result = new PermissionResult(true, 'test.action');
        $result->throwAllowed();
    }

    public function testThrowAllowedDoesNotThrowWhenDenied(): void
    {
        $result = new PermissionResult(false, 'test.action');
        $returned = $result->throwAllowed();

        $this->assertSame($result, $returned);
    }

    public function testThrowIsAliasForThrowDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);

        $result = new PermissionResult(false, 'test.action');
        $result->throw();
    }

    public function testThrowWithCustomMessage(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Custom error message');

        $result = new PermissionResult(false, 'test.action');
        $result->throw('Custom error message');
    }

    public function testThenExecutesWhenAllowed(): void
    {
        $result = new PermissionResult(true, 'test.action');
        $executed = false;

        $result->then(function () use (&$executed) {
            $executed = true;
        });

        $this->assertTrue($executed);
    }

    public function testThenDoesNotExecuteWhenDenied(): void
    {
        $result = new PermissionResult(false, 'test.action');
        $executed = false;

        $result->then(function () use (&$executed) {
            $executed = true;
        });

        $this->assertFalse($executed);
    }

    public function testElseExecutesWhenDenied(): void
    {
        $result = new PermissionResult(false, 'test.action');
        $executed = false;

        $result->else(function () use (&$executed) {
            $executed = true;
        });

        $this->assertTrue($executed);
    }

    public function testElseDoesNotExecuteWhenAllowed(): void
    {
        $result = new PermissionResult(true, 'test.action');
        $executed = false;

        $result->else(function () use (&$executed) {
            $executed = true;
        });

        $this->assertFalse($executed);
    }

    public function testValueReturnsCorrectValue(): void
    {
        $allowed = new PermissionResult(true, 'test.action');
        $denied = new PermissionResult(false, 'test.action');

        $this->assertEquals('yes', $allowed->value('yes', 'no'));
        $this->assertEquals('no', $denied->value('yes', 'no'));
    }

    public function testInvokeReturnsBool(): void
    {
        $allowed = new PermissionResult(true, 'test.action');
        $denied = new PermissionResult(false, 'test.action');

        $this->assertTrue($allowed());
        $this->assertFalse($denied());
    }

    public function testToStringReturnsStatus(): void
    {
        $allowed = new PermissionResult(true, 'test.action');
        $denied = new PermissionResult(false, 'test.action');

        $this->assertEquals('ALLOWED: test.action', (string) $allowed);
        $this->assertEquals('DENIED: test.action', (string) $denied);
    }

    public function testStaticMake(): void
    {
        $result = PermissionResult::make(true, 'test.action');

        $this->assertInstanceOf(PermissionResult::class, $result);
        $this->assertTrue($result->allowed());
    }

    public function testFluentChaining(): void
    {
        $result = new PermissionResult(true, 'test.action');
        $count = 0;

        $result
            ->then(function () use (&$count) {
                $count++;
            })
            ->throwDenied()
            ->then(function () use (&$count) {
                $count++;
            });

        $this->assertEquals(2, $count);
    }
}
