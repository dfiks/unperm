<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Tests\Feature;

use DFiks\UnPerm\Facades\PermissionGate;
use DFiks\UnPerm\Support\PermissionResult;
use DFiks\UnPerm\Tests\Models\User;
use DFiks\UnPerm\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PermissionGateFluentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['unperm.cache.enabled' => false]);
        config(['unperm.superadmins.enabled' => false]);
        $this->artisan('unperm:sync')->assertSuccessful();
    }

    public function testCanReturnsPermissionResult(): void
    {
        PermissionGate::define('test-action', fn() => true);
        
        $user = User::create(['name' => 'Test', 'email' => 'test@example.com']);
        $result = PermissionGate::can('test-action', null, $user);
        
        $this->assertInstanceOf(PermissionResult::class, $result);
        $this->assertTrue($result->allowed());
    }

    public function testCanThrowDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        
        PermissionGate::define('test-action', fn() => false);
        
        $user = User::create(['name' => 'Test', 'email' => 'test@example.com']);
        PermissionGate::can('test-action', null, $user)->throwDenied();
    }

    public function testCanThrowWithDefaultBehavior(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        
        PermissionGate::define('test-action', fn() => false);
        
        $user = User::create(['name' => 'Test', 'email' => 'test@example.com']);
        PermissionGate::can('test-action', null, $user)->throw();
    }

    public function testCanAnyReturnsPermissionResult(): void
    {
        PermissionGate::define('action1', fn() => false);
        PermissionGate::define('action2', fn() => true);
        
        $user = User::create(['name' => 'Test', 'email' => 'test@example.com']);
        $result = PermissionGate::canAny(['action1', 'action2'], null, $user);
        
        $this->assertInstanceOf(PermissionResult::class, $result);
        $this->assertTrue($result->allowed());
    }

    public function testCanAnyThrowDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        
        PermissionGate::define('action1', fn() => false);
        PermissionGate::define('action2', fn() => false);
        
        $user = User::create(['name' => 'Test', 'email' => 'test@example.com']);
        PermissionGate::canAny(['action1', 'action2'], null, $user)->throw();
    }

    public function testCanAllReturnsPermissionResult(): void
    {
        PermissionGate::define('action1', fn() => true);
        PermissionGate::define('action2', fn() => true);
        
        $user = User::create(['name' => 'Test', 'email' => 'test@example.com']);
        $result = PermissionGate::canAll(['action1', 'action2'], null, $user);
        
        $this->assertInstanceOf(PermissionResult::class, $result);
        $this->assertTrue($result->allowed());
    }

    public function testCanAllThrowDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        
        PermissionGate::define('action1', fn() => true);
        PermissionGate::define('action2', fn() => false);
        
        $user = User::create(['name' => 'Test', 'email' => 'test@example.com']);
        PermissionGate::canAll(['action1', 'action2'], null, $user)->throw();
    }

    public function testFluentChainWithActions(): void
    {
        PermissionGate::define('test-action', fn() => true);
        
        $user = User::create(['name' => 'Test', 'email' => 'test@example.com']);
        $executed = false;
        
        PermissionGate::can('test-action', null, $user)
            ->throwDenied()
            ->then(function () use (&$executed) {
                $executed = true;
            });
        
        $this->assertTrue($executed);
    }

    public function testValueMethod(): void
    {
        PermissionGate::define('allowed-action', fn() => true);
        PermissionGate::define('denied-action', fn() => false);
        
        $user = User::create(['name' => 'Test', 'email' => 'test@example.com']);
        
        $allowedValue = PermissionGate::can('allowed-action', null, $user)->value('yes', 'no');
        $deniedValue = PermissionGate::can('denied-action', null, $user)->value('yes', 'no');
        
        $this->assertEquals('yes', $allowedValue);
        $this->assertEquals('no', $deniedValue);
    }

    public function testBackwardCompatibilityWithCheck(): void
    {
        PermissionGate::define('test-action', fn() => true);
        
        $user = User::create(['name' => 'Test', 'email' => 'test@example.com']);
        
        // check() по умолчанию возвращает bool
        $result = PermissionGate::check('test-action', null, $user);
        $this->assertIsBool($result);
        $this->assertTrue($result);
        
        // check() с fluent=true возвращает PermissionResult
        $fluentResult = PermissionGate::check('test-action', null, $user, fluent: true);
        $this->assertInstanceOf(PermissionResult::class, $fluentResult);
    }
}

