<?php

namespace DFiks\UnPerm\Tests\Feature;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Tests\Models\User;
use DFiks\UnPerm\Tests\TestCase;

class PermBitIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'unperm.actions' => [
                'users' => [
                    'view' => 'View users',
                    'create' => 'Create users',
                    'edit' => 'Edit users',
                ],
            ],
        ]);
    }

    public function testChecksActionBySlugViaBitmask(): void
    {
        $user = User::create(['name' => 'Test', 'email' => 'test@test.com']);

        Action::create(['name' => 'View users', 'slug' => 'users.view', 'bitmask' => '1']);
        Action::create(['name' => 'Create users', 'slug' => 'users.create', 'bitmask' => '2']);

        $user->assignAction('users.view');

        $this->assertTrue($user->hasAction('users.view'));
        $this->assertFalse($user->hasAction('users.create'));
    }

    public function testChecksAnyActionViaBitmask(): void
    {
        $user = User::create(['name' => 'Test', 'email' => 'test@test.com']);

        Action::create(['name' => 'View', 'slug' => 'users.view', 'bitmask' => '1']);
        Action::create(['name' => 'Edit', 'slug' => 'users.edit', 'bitmask' => '4']);

        $user->assignAction('users.edit');

        $this->assertTrue($user->hasAnyAction(['users.view', 'users.edit']));
        $this->assertFalse($user->hasAnyAction(['users.view', 'users.create']));
    }

    public function testChecksAllActionsViaBitmask(): void
    {
        $user = User::create(['name' => 'Test', 'email' => 'test@test.com']);

        Action::create(['name' => 'View', 'slug' => 'users.view', 'bitmask' => '1']);
        Action::create(['name' => 'Create', 'slug' => 'users.create', 'bitmask' => '2']);

        $user->assignActions(['users.view', 'users.create']);

        $this->assertTrue($user->hasAllActions(['users.view', 'users.create']));
        $this->assertFalse($user->hasAllActions(['users.view', 'users.create', 'users.edit']));
    }

    public function testWorksWithLargeBitmasks(): void
    {
        $user = User::create(['name' => 'Test', 'email' => 'test@test.com']);

        $largeMask = gmp_strval(gmp_pow(2, 100));
        Action::create(['name' => 'Special', 'slug' => 'special', 'bitmask' => $largeMask]);

        $user->assignAction('special');
        $userMask = $user->getPermissionBitmask();

        $this->assertEquals($largeMask, $userMask);
    }
}
