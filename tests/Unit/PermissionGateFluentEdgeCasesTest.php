<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Tests\Unit;

use DFiks\UnPerm\Facades\PermissionGate;
use DFiks\UnPerm\Support\PermissionResult;
use DFiks\UnPerm\Tests\TestCase;

class PermissionGateFluentEdgeCasesTest extends TestCase
{
    public function testCanReturnsPermissionResultWhenNoUser(): void
    {
        PermissionGate::define('test-action', fn () => true);

        // Без пользователя
        $result = PermissionGate::can('test-action');

        $this->assertInstanceOf(PermissionResult::class, $result);
        $this->assertFalse($result->allowed());
        $this->assertTrue($result->denied());
    }

    public function testCanReturnsPermissionResultWhenRuleNotDefined(): void
    {
        $result = PermissionGate::can('non-existent-action');

        $this->assertInstanceOf(PermissionResult::class, $result);
        $this->assertFalse($result->allowed());
    }

    public function testCanAnyReturnsPermissionResultWithEmptyArray(): void
    {
        $result = PermissionGate::canAny([]);

        $this->assertInstanceOf(PermissionResult::class, $result);
        $this->assertFalse($result->allowed());
    }

    public function testCanAllReturnsPermissionResultWithEmptyArray(): void
    {
        $result = PermissionGate::canAll([]);

        $this->assertInstanceOf(PermissionResult::class, $result);
        $this->assertTrue($result->allowed()); // All of nothing is true
    }

    public function testCheckWithFluentFalseReturnsBool(): void
    {
        PermissionGate::define('test-action', fn () => true);

        $result = PermissionGate::check('test-action', fluent: false);

        $this->assertIsBool($result);
        $this->assertFalse($result); // No user
    }

    public function testCheckWithFluentTrueReturnsPermissionResult(): void
    {
        PermissionGate::define('test-action', fn () => true);

        $result = PermissionGate::check('test-action', fluent: true);

        $this->assertInstanceOf(PermissionResult::class, $result);
    }
}
