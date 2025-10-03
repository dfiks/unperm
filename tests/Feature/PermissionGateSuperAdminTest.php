<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Tests\Feature;

use DFiks\UnPerm\Facades\PermissionGate;
use DFiks\UnPerm\Tests\Models\User;
use DFiks\UnPerm\Tests\TestCase;

class PermissionGateSuperAdminTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['unperm.cache.enabled' => false]);
        $this->artisan('unperm:sync')->assertSuccessful();
    }

    public function testSuperAdminBypassesAllChecks(): void
    {
        config(['unperm.superadmins.enabled' => true]);
        config(['unperm.superadmins.models' => [User::class]]);

        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
        ]);

        // Определяем правило которое всегда возвращает false
        PermissionGate::define('impossible-action', fn() => false);

        // Суперадмин должен пройти проверку
        $this->assertTrue(PermissionGate::check('impossible-action', null, $user));
        $this->assertTrue(PermissionGate::isSuperAdmin($user));
    }

    public function testNormalUserChecksRules(): void
    {
        config(['unperm.superadmins.enabled' => true]);
        config(['unperm.superadmins.models' => []]);

        $user = User::create([
            'name' => 'Normal User',
            'email' => 'normal@example.com',
        ]);

        PermissionGate::define('impossible-action', fn() => false);

        $this->assertFalse(PermissionGate::check('impossible-action', null, $user));
        $this->assertFalse(PermissionGate::isSuperAdmin($user));
    }

    public function testSuperAdminHelper(): void
    {
        config(['unperm.superadmins.enabled' => true]);
        config(['unperm.superadmins.emails' => ['admin@example.com']]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ]);

        $normal = User::create([
            'name' => 'Normal',
            'email' => 'normal@example.com',
        ]);

        $this->assertTrue(is_superadmin($admin));
        $this->assertFalse(is_superadmin($normal));
    }

    public function testSuperAdminReason(): void
    {
        config(['unperm.superadmins.enabled' => true]);
        config(['unperm.superadmins.ids' => [999]]);

        $user = User::create([
            'name' => 'Test',
            'email' => 'test@example.com',
        ]);

        // Обновляем ID на 999
        $user->id = 999;
        $user->save();

        $reason = PermissionGate::getSuperAdminReason($user);
        $this->assertNotNull($reason);
        $this->assertStringContainsString('ID', $reason);
    }

    public function testMultipleModelsSuperadmin(): void
    {
        config(['unperm.superadmins.enabled' => true]);
        config(['unperm.superadmins.models' => [
            User::class,
            \DFiks\UnPerm\Tests\Models\Folder::class,
        ]]);

        $user = User::create([
            'name' => 'User Model',
            'email' => 'user@example.com',
        ]);

        $folder = \DFiks\UnPerm\Tests\Models\Folder::create([
            'name' => 'Folder Model',
        ]);

        $this->assertTrue(PermissionGate::isSuperAdmin($user));
        $this->assertTrue(PermissionGate::isSuperAdmin($folder));
    }
}

