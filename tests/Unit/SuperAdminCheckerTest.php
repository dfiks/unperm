<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Tests\Unit;

use DFiks\UnPerm\Support\SuperAdminChecker;
use DFiks\UnPerm\Tests\Models\User;
use DFiks\UnPerm\Tests\TestCase;

class SuperAdminCheckerTest extends TestCase
{
    protected SuperAdminChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = new SuperAdminChecker();
    }

    public function testCheckByModel(): void
    {
        config(['unperm.superadmins.enabled' => true]);
        config(['unperm.superadmins.models' => [User::class]]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->assertTrue($this->checker->check($user));
        $this->assertStringContainsString('Модель', $this->checker->getReason($user));
    }

    public function testCheckById(): void
    {
        config(['unperm.superadmins.enabled' => true]);
        config(['unperm.superadmins.models' => []]);
        
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        config(['unperm.superadmins.ids' => [$user->id]]);

        $this->assertTrue($this->checker->check($user));
        $this->assertStringContainsString('ID', $this->checker->getReason($user));
    }

    public function testCheckByEmail(): void
    {
        config(['unperm.superadmins.enabled' => true]);
        config(['unperm.superadmins.models' => []]);
        config(['unperm.superadmins.ids' => []]);
        config(['unperm.superadmins.emails' => ['admin@example.com']]);

        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ]);

        $this->assertTrue($this->checker->check($user));
        $this->assertStringContainsString('Email', $this->checker->getReason($user));

        $normalUser = User::create([
            'name' => 'Normal',
            'email' => 'normal@example.com',
        ]);

        $this->assertFalse($this->checker->check($normalUser));
    }

    public function testCheckByAction(): void
    {
        config(['unperm.cache.enabled' => false]); // Отключаем кеш для теста
        config(['unperm.superadmins.enabled' => true]);
        config(['unperm.superadmins.models' => []]);
        config(['unperm.superadmins.ids' => []]);
        config(['unperm.superadmins.emails' => []]);
        // Добавляем superadmin action в конфиг перед синхронизацией
        config(['unperm.actions.system.superadmin' => 'Superadmin Access']);
        config(['unperm.superadmins.action' => 'system.superadmin']);

        $this->artisan('unperm:sync')->assertSuccessful();

        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ]);

        $action = \DFiks\UnPerm\Models\Action::where('slug', 'system.superadmin')->first();
        $this->assertNotNull($action, 'Superadmin action should exist');
        $this->assertNotEquals('0', $action->bitmask, 'Action should have non-zero bitmask');

        $user->assignAction($action);
        $user->load('actions');

        $this->assertTrue($this->checker->check($user), 'User should be identified as superadmin');
        $this->assertStringContainsString('action', $this->checker->getReason($user));
    }

    public function testCheckByCallback(): void
    {
        config(['unperm.superadmins.enabled' => true]);
        config(['unperm.superadmins.models' => []]);
        config(['unperm.superadmins.ids' => []]);
        config(['unperm.superadmins.emails' => []]);
        config(['unperm.superadmins.callback' => fn($user) => $user->email === 'callback@example.com']);

        $user = User::create([
            'name' => 'Callback User',
            'email' => 'callback@example.com',
        ]);

        $this->assertTrue($this->checker->check($user));
        $this->assertStringContainsString('Callback', $this->checker->getReason($user));
    }

    public function testDisabledSuperadmins(): void
    {
        config(['unperm.superadmins.enabled' => false]);
        config(['unperm.superadmins.models' => [User::class]]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->assertFalse($this->checker->check($user));
        $this->assertNull($this->checker->getReason($user));
    }

    public function testPriorityOrder(): void
    {
        config(['unperm.superadmins.enabled' => true]);
        config(['unperm.superadmins.models' => [User::class]]);
        config(['unperm.superadmins.emails' => ['priority@example.com']]);

        $user = User::create([
            'name' => 'Priority User',
            'email' => 'priority@example.com',
        ]);

        $this->assertTrue($this->checker->check($user));
        
        // Должно проверяться по модели первым (более высокий приоритет)
        $reason = $this->checker->getReason($user);
        $this->assertStringContainsString('Модель', $reason);
    }
}

