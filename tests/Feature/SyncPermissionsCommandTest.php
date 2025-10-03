<?php

namespace DFiks\UnPerm\Tests\Feature;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Group;
use DFiks\UnPerm\Models\Role;
use DFiks\UnPerm\Tests\TestCase;

class SyncPermissionsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'unperm.actions' => [
                'users' => ['view' => 'View'],
                'posts' => ['view' => 'View'],
            ],
            'unperm.roles' => [
                'admin' => [
                    'name' => 'Admin',
                    'actions' => ['*'],
                ],
            ],
            'unperm.groups' => [
                'team' => [
                    'name' => 'Team',
                    'roles' => ['admin'],
                ],
            ],
        ]);
    }

    public function testSyncsAllPermissions(): void
    {
        $this->artisan('unperm:sync')
            ->assertSuccessful();

        $this->assertEquals(2, Action::count());
        $this->assertEquals(1, Role::count());
        $this->assertEquals(1, Group::count());
    }

    public function testSyncsInCorrectOrder(): void
    {
        $this->artisan('unperm:sync');

        $admin = Role::where('slug', 'admin')->first();
        $team = Group::where('slug', 'team')->first();

        $this->assertNotNull($admin);
        $this->assertNotNull($team);

        // Группа должна иметь роль
        $this->assertTrue($team->roles->contains($admin));
    }

    public function testFreshOptionDeletesAll(): void
    {
        Action::create(['name' => 'Old', 'slug' => 'old', 'bitmask' => '0']);
        Role::create(['name' => 'Old', 'slug' => 'old', 'bitmask' => '0']);
        Group::create(['name' => 'Old', 'slug' => 'old', 'bitmask' => '0']);

        $this->artisan('unperm:sync', ['--fresh' => true])
            ->assertSuccessful();

        $this->assertDatabaseMissing('actions', ['slug' => 'old']);
        $this->assertDatabaseMissing('roles', ['slug' => 'old']);
        $this->assertDatabaseMissing('groups', ['slug' => 'old']);
    }
}
