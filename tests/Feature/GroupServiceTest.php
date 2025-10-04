<?php

namespace DFiks\UnPerm\Tests\Feature;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Group;
use DFiks\UnPerm\Models\Role;
use DFiks\UnPerm\Services\GroupService;
use DFiks\UnPerm\Tests\Models\User;
use DFiks\UnPerm\Tests\TestCase;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GroupServiceTest extends TestCase
{
    use RefreshDatabase;

    private GroupService $service;
    private User $user;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(GroupService::class);

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

    public function testGetAllReturnsAllGroups(): void
    {
        Group::create(['slug' => 'developers', 'name' => 'Developers']);
        Group::create(['slug' => 'managers', 'name' => 'Managers']);

        $groups = $this->service->withoutAuthorization()->getAll();

        $this->assertGreaterThanOrEqual(2, $groups->count());
    }

    public function testPaginateReturnsGroups(): void
    {
        Group::create(['slug' => 'developers', 'name' => 'Developers']);
        Group::create(['slug' => 'managers', 'name' => 'Managers']);

        $paginated = $this->service->withoutAuthorization()->paginate(10);

        $this->assertGreaterThanOrEqual(2, $paginated->total());
    }

    public function testPaginateWithSearch(): void
    {
        Group::create(['slug' => 'unique-group', 'name' => 'Unique Group']);

        $result = $this->service->withoutAuthorization()->paginate(10, 'unique');

        $this->assertGreaterThanOrEqual(1, $result->total());
    }

    public function testFindReturnsGroup(): void
    {
        $group = Group::create(['slug' => 'test-group', 'name' => 'Test Group']);

        $found = $this->service->withoutAuthorization()->find($group->id);

        $this->assertNotNull($found);
        $this->assertEquals($group->id, $found->id);
    }

    public function testFindBySlugReturnsGroup(): void
    {
        Group::create(['slug' => 'find-by-slug', 'name' => 'Find By Slug']);

        $found = $this->service->withoutAuthorization()->findBySlug('find-by-slug');

        $this->assertNotNull($found);
        $this->assertEquals('find-by-slug', $found->slug);
    }

    public function testCreateRequiresManagePermission(): void
    {
        $this->actingAs($this->user);

        $this->expectException(AuthorizationException::class);
        $this->service->create([
            'slug' => 'new-group',
            'name' => 'New Group',
        ]);
    }

    public function testCreateCreatesGroup(): void
    {
        $group = $this->service->withoutAuthorization()->create([
            'slug' => 'new-group',
            'name' => 'New Group',
            'description' => 'Test Group',
        ]);

        $this->assertNotNull($group);
        $this->assertEquals('new-group', $group->slug);
        $this->assertDatabaseHas('groups', ['slug' => 'new-group']);
    }

    public function testCreateWithActionsAndRoles(): void
    {
        $action1 = Action::create(['name' => 'Action 1', 'slug' => 'action1', 'description' => 'Action 1']);
        $action2 = Action::create(['name' => 'Action 2', 'slug' => 'action2', 'description' => 'Action 2']);
        $role1 = Role::create(['slug' => 'role1', 'name' => 'Role 1']);

        $group = $this->service->withoutAuthorization()->create([
            'slug' => 'group-with-perms',
            'name' => 'Group With Permissions',
            'action_ids' => [$action1->id, $action2->id],
            'role_ids' => [$role1->id],
        ]);

        $this->assertCount(2, $group->actions);
        $this->assertCount(1, $group->roles);
    }

    public function testUpdateRequiresManagePermission(): void
    {
        $this->actingAs($this->user);

        $group = Group::create(['slug' => 'update-group', 'name' => 'Update Group']);

        $this->expectException(AuthorizationException::class);
        $this->service->update($group, ['name' => 'Updated Name']);
    }

    public function testUpdateUpdatesGroup(): void
    {
        $group = Group::create(['slug' => 'update-group', 'name' => 'Original Name']);

        $updated = $this->service->withoutAuthorization()->update($group, [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
        ]);

        $this->assertEquals('Updated Name', $updated->name);
        $this->assertEquals('Updated Description', $updated->description);
    }

    public function testDeleteRequiresManagePermission(): void
    {
        $this->actingAs($this->user);

        $group = Group::create(['slug' => 'delete-group', 'name' => 'Delete Group']);

        $this->expectException(AuthorizationException::class);
        $this->service->delete($group);
    }

    public function testDeleteDeletesGroup(): void
    {
        $group = Group::create(['slug' => 'delete-group', 'name' => 'Delete Group']);

        $result = $this->service->withoutAuthorization()->delete($group);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('groups', ['id' => $group->id]);
    }

    public function testAttachActionAttachesAction(): void
    {
        $group = Group::create(['slug' => 'test-group', 'name' => 'Test Group']);
        $action = Action::create(['name' => 'Test Action', 'slug' => 'test-action', 'description' => 'Test']);

        $this->service->withoutAuthorization()->attachAction($group, $action);

        $this->assertTrue($group->fresh()->actions->contains($action));
    }

    public function testDetachActionDetachesAction(): void
    {
        $group = Group::create(['slug' => 'test-group', 'name' => 'Test Group']);
        $action = Action::create(['name' => 'Test Action', 'slug' => 'test-action', 'description' => 'Test']);

        $group->actions()->attach($action);

        $this->service->withoutAuthorization()->detachAction($group, $action);

        $this->assertFalse($group->fresh()->actions->contains($action));
    }

    public function testSyncActionsSyncsActions(): void
    {
        $group = Group::create(['slug' => 'test-group', 'name' => 'Test Group']);
        $action1 = Action::create(['name' => 'Action 1', 'slug' => 'action1', 'description' => 'Action 1']);
        $action2 = Action::create(['name' => 'Action 2', 'slug' => 'action2', 'description' => 'Action 2']);

        $this->service->withoutAuthorization()->syncActions($group, [$action1->id, $action2->id]);

        $this->assertCount(2, $group->fresh()->actions);
    }

    public function testAttachRoleAttachesRole(): void
    {
        $group = Group::create(['slug' => 'test-group', 'name' => 'Test Group']);
        $role = Role::create(['slug' => 'test-role', 'name' => 'Test Role']);

        $this->service->withoutAuthorization()->attachRole($group, $role);

        $this->assertTrue($group->fresh()->roles->contains($role));
    }

    public function testDetachRoleDetachesRole(): void
    {
        $group = Group::create(['slug' => 'test-group', 'name' => 'Test Group']);
        $role = Role::create(['slug' => 'test-role', 'name' => 'Test Role']);

        $group->roles()->attach($role);

        $this->service->withoutAuthorization()->detachRole($group, $role);

        $this->assertFalse($group->fresh()->roles->contains($role));
    }

    public function testSyncRolesSyncsRoles(): void
    {
        $group = Group::create(['slug' => 'test-group', 'name' => 'Test Group']);
        $role1 = Role::create(['slug' => 'role1', 'name' => 'Role 1']);
        $role2 = Role::create(['slug' => 'role2', 'name' => 'Role 2']);

        $this->service->withoutAuthorization()->syncRoles($group, [$role1->id, $role2->id]);

        $this->assertCount(2, $group->fresh()->roles);
    }

    public function testSyncSyncsGroups(): void
    {
        $action1 = Action::create(['name' => 'Action 1', 'slug' => 'action1', 'description' => 'Action 1']);
        $role1 = Role::create(['slug' => 'role1', 'name' => 'Role 1']);

        $this->service->withoutAuthorization()->sync([
            'group1' => [
                'name' => 'Group 1',
                'description' => 'Description 1',
                'actions' => ['action1'],
                'roles' => ['role1'],
            ],
            'group2' => [
                'name' => 'Group 2',
            ],
        ]);

        $this->assertDatabaseHas('groups', ['slug' => 'group1']);
        $this->assertDatabaseHas('groups', ['slug' => 'group2']);

        $group1 = Group::where('slug', 'group1')->first();
        $this->assertTrue($group1->actions->contains($action1));
        $this->assertTrue($group1->roles->contains($role1));
    }

    public function testWithoutAuthorizationSkipsPermissionChecks(): void
    {
        $this->actingAs($this->user);

        $groups = $this->service->withoutAuthorization()->getAll();

        $this->assertNotNull($groups);
    }
}
