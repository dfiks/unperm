<?php

namespace DFiks\UnPerm\Tests\Feature;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Role;
use DFiks\UnPerm\Tests\TestCase;

class SyncRolesCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Сначала создаем actions
        config([
            'unperm.actions' => [
                'users' => [
                    'view' => 'View users',
                    'create' => 'Create users',
                ],
                'posts' => [
                    'view' => 'View posts',
                    'edit' => 'Edit posts',
                ],
            ],
            'unperm.roles' => [
                'admin' => [
                    'name' => 'Administrator',
                    'description' => 'Full access',
                    'actions' => ['users.*', 'posts.*'],
                ],
                'editor' => [
                    'name' => 'Editor',
                    'actions' => ['posts.view', 'posts.edit'],
                ],
            ],
        ]);
        
        $this->artisan('unperm:sync-actions');
    }

    public function testSyncsRolesFromConfig(): void
    {
        $this->artisan('unperm:sync-roles')
            ->assertSuccessful();

        $this->assertDatabaseHas('roles', ['slug' => 'admin', 'name' => 'Administrator']);
        $this->assertDatabaseHas('roles', ['slug' => 'editor', 'name' => 'Editor']);
        
        $this->assertEquals(2, Role::count());
    }

    public function testAssignsActionsToRoles(): void
    {
        $this->artisan('unperm:sync-roles');

        $admin = Role::where('slug', 'admin')->first();
        $this->assertCount(4, $admin->actions);

        $editor = Role::where('slug', 'editor')->first();
        $this->assertCount(2, $editor->actions);
    }

    public function testSupportsWildcardPatterns(): void
    {
        $this->artisan('unperm:sync-roles');

        $admin = Role::where('slug', 'admin')->first();
        
        // Должен иметь все users.* и posts.*
        $actionSlugs = $admin->actions->pluck('slug')->toArray();
        $this->assertContains('users.view', $actionSlugs);
        $this->assertContains('users.create', $actionSlugs);
        $this->assertContains('posts.view', $actionSlugs);
        $this->assertContains('posts.edit', $actionSlugs);
    }

    public function testCalculatesBitmasks(): void
    {
        $this->artisan('unperm:sync-roles');

        $admin = Role::where('slug', 'admin')->first();
        $editor = Role::where('slug', 'editor')->first();

        $this->assertNotEquals('0', $admin->bitmask);
        $this->assertNotEquals('0', $editor->bitmask);
        
        // Admin должен иметь больше прав
        $adminMask = gmp_init($admin->bitmask);
        $editorMask = gmp_init($editor->bitmask);
        $this->assertTrue(gmp_cmp($adminMask, $editorMask) > 0);
    }

    public function testFreshOptionDeletesExisting(): void
    {
        Role::create(['name' => 'Old Role', 'slug' => 'old-role', 'bitmask' => '0']);

        $this->artisan('unperm:sync-roles', ['--fresh' => true])
            ->assertSuccessful();

        $this->assertDatabaseMissing('roles', ['slug' => 'old-role']);
        $this->assertEquals(2, Role::count());
    }

    public function testUpdatesExistingRoles(): void
    {
        Role::create(['name' => 'Old Name', 'slug' => 'admin', 'bitmask' => '0']);

        $this->artisan('unperm:sync-roles');

        $role = Role::where('slug', 'admin')->first();
        $this->assertEquals('Administrator', $role->name);
        $this->assertEquals('Full access', $role->description);
    }

    public function testRemovesOrphanedRoles(): void
    {
        $this->artisan('unperm:sync-roles');
        Role::create(['name' => 'Orphan', 'slug' => 'orphan', 'bitmask' => '0']);

        $this->assertEquals(3, Role::count());

        $this->artisan('unperm:sync-roles');

        $this->assertDatabaseMissing('roles', ['slug' => 'orphan']);
        $this->assertEquals(2, Role::count());
    }
}

