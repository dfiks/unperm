<?php

namespace DFiks\UnPerm\Tests\Feature;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Role;
use DFiks\UnPerm\Services\RoleService;
use DFiks\UnPerm\Tests\Models\User;
use DFiks\UnPerm\Tests\TestCase;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoleServiceTest extends TestCase
{
    use RefreshDatabase;

    private RoleService $service;
    private User $user;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RoleService::class);

        $this->user = User::create([
            'name' => 'Regular User',
            'email' => 'user@test.com',
        ]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
        ]);

        $viewAction = Action::create([
            'name' => 'View Permissions',
            'slug' => 'admin.permissions.view',
            'description' => 'View permissions',
        ]);

        $manageAction = Action::create([
            'name' => 'Manage Permissions',
            'slug' => 'admin.permissions.manage',
            'description' => 'Manage permissions',
        ]);

        $this->admin->assignAction($viewAction);
        $this->admin->assignAction($manageAction);
        $this->admin = $this->admin->fresh();
    }

    public function testGetAllRequiresViewPermission(): void
    {
        $this->actingAs($this->user);

        $this->expectException(AuthorizationException::class);
        $this->service->getAll();
    }

    public function testGetAllReturnsAllRoles(): void
    {
        Role::create(['slug' => 'editor', 'name' => 'Editor']);
        Role::create(['slug' => 'viewer', 'name' => 'Viewer']);

        $roles = $this->service->withoutAuthorization()->getAll();

        $this->assertGreaterThanOrEqual(2, $roles->count());
    }

    public function testPaginateReturnsRoles(): void
    {
        Role::create(['slug' => 'editor', 'name' => 'Editor']);
        Role::create(['slug' => 'viewer', 'name' => 'Viewer']);

        $paginated = $this->service->withoutAuthorization()->paginate(10);

        $this->assertGreaterThanOrEqual(2, $paginated->total());
    }

    public function testPaginateWithSearch(): void
    {
        Role::create(['slug' => 'unique-role', 'name' => 'Unique Role']);

        $result = $this->service->withoutAuthorization()->paginate(10, 'unique');

        $this->assertGreaterThanOrEqual(1, $result->total());
    }

    public function testFindReturnsRole(): void
    {
        $role = Role::create(['slug' => 'test-role', 'name' => 'Test Role']);

        $found = $this->service->withoutAuthorization()->find($role->id);

        $this->assertNotNull($found);
        $this->assertEquals($role->id, $found->id);
    }

    public function testFindBySlugReturnsRole(): void
    {
        Role::create(['slug' => 'find-by-slug', 'name' => 'Find By Slug']);

        $found = $this->service->withoutAuthorization()->findBySlug('find-by-slug');

        $this->assertNotNull($found);
        $this->assertEquals('find-by-slug', $found->slug);
    }

    public function testCreateRequiresManagePermission(): void
    {
        $this->actingAs($this->user);

        $this->expectException(AuthorizationException::class);
        $this->service->create([
            'slug' => 'new-role',
            'name' => 'New Role',
        ]);
    }

    public function testCreateCreatesRole(): void
    {
        $role = $this->service->withoutAuthorization()->create([
            'slug' => 'new-role',
            'name' => 'New Role',
            'description' => 'Test Role',
        ]);

        $this->assertNotNull($role);
        $this->assertEquals('new-role', $role->slug);
        $this->assertDatabaseHas('roles', ['slug' => 'new-role']);
    }

    public function testCreateWithActions(): void
    {
        $action1 = Action::create(['name' => 'Action 1', 'slug' => 'action1', 'description' => 'Action 1']);
        $action2 = Action::create(['name' => 'Action 2', 'slug' => 'action2', 'description' => 'Action 2']);

        $role = $this->service->withoutAuthorization()->create([
            'slug' => 'role-with-actions',
            'name' => 'Role With Actions',
            'action_ids' => [$action1->id, $action2->id],
        ]);

        $this->assertCount(2, $role->actions);
    }

    public function testUpdateRequiresManagePermission(): void
    {
        $this->actingAs($this->user);

        $role = Role::create(['slug' => 'update-role', 'name' => 'Update Role']);

        $this->expectException(AuthorizationException::class);
        $this->service->update($role, ['name' => 'Updated Name']);
    }

    public function testUpdateUpdatesRole(): void
    {
        $role = Role::create(['slug' => 'update-role', 'name' => 'Original Name']);

        $updated = $this->service->withoutAuthorization()->update($role, [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
        ]);

        $this->assertEquals('Updated Name', $updated->name);
        $this->assertEquals('Updated Description', $updated->description);
    }

    public function testDeleteRequiresManagePermission(): void
    {
        $this->actingAs($this->user);

        $role = Role::create(['slug' => 'delete-role', 'name' => 'Delete Role']);

        $this->expectException(AuthorizationException::class);
        $this->service->delete($role);
    }

    public function testDeleteDeletesRole(): void
    {
        $role = Role::create(['slug' => 'delete-role', 'name' => 'Delete Role']);

        $result = $this->service->withoutAuthorization()->delete($role);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function testAttachActionRequiresManagePermission(): void
    {
        $this->actingAs($this->user);

        $role = Role::create(['slug' => 'test-role', 'name' => 'Test Role']);
        $action = Action::create(['name' => 'Test Action', 'slug' => 'test-action', 'description' => 'Test']);

        $this->expectException(AuthorizationException::class);
        $this->service->attachAction($role, $action);
    }

    public function testAttachActionAttachesAction(): void
    {
        $role = Role::create(['slug' => 'test-role', 'name' => 'Test Role']);
        $action = Action::create(['name' => 'Test Action', 'slug' => 'test-action', 'description' => 'Test']);

        $this->service->withoutAuthorization()->attachAction($role, $action);

        $this->assertTrue($role->fresh()->actions->contains($action));
    }

    public function testDetachActionDetachesAction(): void
    {
        $role = Role::create(['slug' => 'test-role', 'name' => 'Test Role']);
        $action = Action::create(['name' => 'Test Action', 'slug' => 'test-action', 'description' => 'Test']);

        $role->actions()->attach($action);

        $this->service->withoutAuthorization()->detachAction($role, $action);

        $this->assertFalse($role->fresh()->actions->contains($action));
    }

    public function testSyncActionsSyncsActions(): void
    {
        $role = Role::create(['slug' => 'test-role', 'name' => 'Test Role']);
        $action1 = Action::create(['name' => 'Action 1', 'slug' => 'action1', 'description' => 'Action 1']);
        $action2 = Action::create(['name' => 'Action 2', 'slug' => 'action2', 'description' => 'Action 2']);

        $this->service->withoutAuthorization()->syncActions($role, [$action1->id, $action2->id]);

        $this->assertCount(2, $role->fresh()->actions);
    }

    public function testSyncSyncsRoles(): void
    {
        $action1 = Action::create(['name' => 'Action 1', 'slug' => 'action1', 'description' => 'Action 1']);
        $action2 = Action::create(['name' => 'Action 2', 'slug' => 'action2', 'description' => 'Action 2']);

        $this->service->withoutAuthorization()->sync([
            'role1' => [
                'name' => 'Role 1',
                'description' => 'Description 1',
                'actions' => ['action1'],
            ],
            'role2' => [
                'name' => 'Role 2',
                'actions' => ['action2'],
            ],
        ]);

        $this->assertDatabaseHas('roles', ['slug' => 'role1']);
        $this->assertDatabaseHas('roles', ['slug' => 'role2']);

        $role1 = Role::where('slug', 'role1')->first();
        $this->assertTrue($role1->actions->contains($action1));
    }

    public function testWithoutAuthorizationSkipsPermissionChecks(): void
    {
        $this->actingAs($this->user);

        $roles = $this->service->withoutAuthorization()->getAll();

        $this->assertNotNull($roles);
    }
}
