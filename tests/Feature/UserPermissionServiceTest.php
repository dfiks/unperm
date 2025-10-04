<?php

namespace DFiks\UnPerm\Tests\Feature;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Group;
use DFiks\UnPerm\Models\Role;
use DFiks\UnPerm\Services\UserPermissionService;
use DFiks\UnPerm\Tests\Models\User;
use DFiks\UnPerm\Tests\TestCase;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserPermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserPermissionService $service;
    private User $regularUser;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(UserPermissionService::class);

        $this->regularUser = User::create([
            'name' => 'Regular User',
            'email' => 'user@test.com',
        ]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
        ]);

        $manageAction = Action::create([
            'name' => 'Manage Users',
            'slug' => 'admin.users.manage',
            'description' => 'Manage users',
        ]);

        $this->admin->assignAction($manageAction);
        $this->admin = $this->admin->fresh();
    }

    public function testGetUsersReturnsUsers(): void
    {
        User::create(['name' => 'Test User 1', 'email' => 'test1@test.com']);
        User::create(['name' => 'Test User 2', 'email' => 'test2@test.com']);

        $paginated = $this->service->withoutAuthorization()->getUsers(User::class, 10);

        $this->assertGreaterThanOrEqual(4, $paginated->total());
    }

    public function testGetUsersWithSearch(): void
    {
        User::create(['name' => 'Unique Name', 'email' => 'unique@test.com']);

        $result = $this->service->withoutAuthorization()->getUsers(User::class, 10, 'Unique');

        $this->assertGreaterThanOrEqual(1, $result->total());
    }

    public function testGetUserReturnsUser(): void
    {
        $user = User::create(['name' => 'Test User', 'email' => 'test@test.com']);

        $found = $this->service->withoutAuthorization()->getUser(User::class, $user->id);

        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
    }

    public function testAssignActionRequiresManagePermission(): void
    {
        $this->actingAs($this->regularUser);

        $action = Action::create(['name' => 'Test Action', 'slug' => 'test.action', 'description' => 'Test']);
        $user = User::create(['name' => 'Test User', 'email' => 'test@test.com']);

        $this->expectException(AuthorizationException::class);
        $this->service->assignAction($user, $action);
    }

    public function testAssignActionAssignsAction(): void
    {
        $action = Action::create(['name' => 'Test Action', 'slug' => 'test.action', 'description' => 'Test']);
        $user = User::create(['name' => 'Test User', 'email' => 'test@test.com']);

        $this->service->withoutAuthorization()->assignAction($user, $action);

        $this->assertTrue($user->fresh()->actions->contains($action));
    }

    public function testRemoveActionRemovesAction(): void
    {
        $action = Action::create(['name' => 'Test Action', 'slug' => 'test.action', 'description' => 'Test']);
        $user = User::create(['name' => 'Test User', 'email' => 'test@test.com']);
        $user->assignAction($action);

        $this->service->withoutAuthorization()->removeAction($user, $action);

        $this->assertFalse($user->fresh()->hasAction($action));
    }

    public function testAssignRoleRequiresManagePermission(): void
    {
        $this->actingAs($this->regularUser);

        $role = Role::create(['slug' => 'test-role', 'name' => 'Test Role']);
        $user = User::create(['name' => 'Test User', 'email' => 'test@test.com']);

        $this->expectException(AuthorizationException::class);
        $this->service->assignRole($user, $role);
    }

    public function testAssignRoleAssignsRole(): void
    {
        $role = Role::create(['slug' => 'test-role', 'name' => 'Test Role']);
        $user = User::create(['name' => 'Test User', 'email' => 'test@test.com']);

        $this->service->withoutAuthorization()->assignRole($user, $role);

        $this->assertTrue($user->fresh()->hasRole($role));
    }

    public function testRemoveRoleRemovesRole(): void
    {
        $role = Role::create(['slug' => 'test-role', 'name' => 'Test Role']);
        $user = User::create(['name' => 'Test User', 'email' => 'test@test.com']);
        $user->assignRole($role);

        $this->service->withoutAuthorization()->removeRole($user, $role);

        $this->assertFalse($user->fresh()->hasRole($role));
    }

    public function testAssignGroupRequiresManagePermission(): void
    {
        $this->actingAs($this->regularUser);

        $group = Group::create(['slug' => 'test-group', 'name' => 'Test Group']);
        $user = User::create(['name' => 'Test User', 'email' => 'test@test.com']);

        $this->expectException(AuthorizationException::class);
        $this->service->assignGroup($user, $group);
    }

    public function testAssignGroupAssignsGroup(): void
    {
        $group = Group::create(['slug' => 'test-group', 'name' => 'Test Group']);
        $user = User::create(['name' => 'Test User', 'email' => 'test@test.com']);

        $this->service->withoutAuthorization()->assignGroup($user, $group);

        $this->assertTrue($user->fresh()->hasGroup($group));
    }

    public function testRemoveGroupRemovesGroup(): void
    {
        $group = Group::create(['slug' => 'test-group', 'name' => 'Test Group']);
        $user = User::create(['name' => 'Test User', 'email' => 'test@test.com']);
        $user->assignGroup($group);

        $this->service->withoutAuthorization()->removeGroup($user, $group);

        $this->assertFalse($user->fresh()->hasGroup($group));
    }

    public function testSyncActionsSyncsActions(): void
    {
        $action1 = Action::create(['name' => 'Action 1', 'slug' => 'action1', 'description' => 'Action 1']);
        $action2 = Action::create(['name' => 'Action 2', 'slug' => 'action2', 'description' => 'Action 2']);
        $user = User::create(['name' => 'Test User', 'email' => 'test@test.com']);

        $this->service->withoutAuthorization()->syncActions($user, [$action1->id, $action2->id]);

        $this->assertCount(2, $user->fresh()->actions);
    }

    public function testSyncRolesSyncsRoles(): void
    {
        $role1 = Role::create(['slug' => 'role1', 'name' => 'Role 1']);
        $role2 = Role::create(['slug' => 'role2', 'name' => 'Role 2']);
        $user = User::create(['name' => 'Test User', 'email' => 'test@test.com']);

        $this->service->withoutAuthorization()->syncRoles($user, [$role1->id, $role2->id]);

        $this->assertCount(2, $user->fresh()->roles);
    }

    public function testSyncGroupsSyncsGroups(): void
    {
        $group1 = Group::create(['slug' => 'group1', 'name' => 'Group 1']);
        $group2 = Group::create(['slug' => 'group2', 'name' => 'Group 2']);
        $user = User::create(['name' => 'Test User', 'email' => 'test@test.com']);

        $this->service->withoutAuthorization()->syncGroups($user, [$group1->id, $group2->id]);

        $this->assertCount(2, $user->fresh()->groups);
    }

    public function testBulkAssignRoleAssignsRoleToMultipleUsers(): void
    {
        $role = Role::create(['slug' => 'bulk-role', 'name' => 'Bulk Role']);
        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@test.com']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@test.com']);

        $count = $this->service->withoutAuthorization()->bulkAssignRole([$user1->id, $user2->id], User::class, $role);

        $this->assertEquals(2, $count);
        $this->assertTrue($user1->fresh()->hasRole($role));
        $this->assertTrue($user2->fresh()->hasRole($role));
    }

    public function testBulkAssignGroupAssignsGroupToMultipleUsers(): void
    {
        $group = Group::create(['slug' => 'bulk-group', 'name' => 'Bulk Group']);
        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@test.com']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@test.com']);

        $count = $this->service->withoutAuthorization()->bulkAssignGroup([$user1->id, $user2->id], User::class, $group);

        $this->assertEquals(2, $count);
        $this->assertTrue($user1->fresh()->hasGroup($group));
        $this->assertTrue($user2->fresh()->hasGroup($group));
    }

    public function testGetAllPermissionsReturnsAllPermissions(): void
    {
        $action = Action::create(['name' => 'Test Action', 'slug' => 'test.action', 'description' => 'Test']);
        $role = Role::create(['slug' => 'test-role', 'name' => 'Test Role']);
        $group = Group::create(['slug' => 'test-group', 'name' => 'Test Group']);

        $user = User::create(['name' => 'Test User', 'email' => 'test@test.com']);
        $user->assignAction($action);
        $user->assignRole($role);
        $user->assignGroup($group);

        $permissions = $this->service->withoutAuthorization()->getAllPermissions($user);

        $this->assertArrayHasKey('direct_actions', $permissions);
        $this->assertArrayHasKey('roles', $permissions);
        $this->assertArrayHasKey('groups', $permissions);
        $this->assertArrayHasKey('resource_actions', $permissions);
        $this->assertArrayHasKey('bitmask', $permissions);
        $this->assertArrayHasKey('is_superadmin', $permissions);

        $this->assertCount(1, $permissions['direct_actions']);
        $this->assertCount(1, $permissions['roles']);
        $this->assertCount(1, $permissions['groups']);
    }

    public function testGetAvailableUserModelsReturnsModels(): void
    {
        $models = $this->service->withoutAuthorization()->getAvailableUserModels();

        $this->assertIsArray($models);
    }

    public function testWithoutAuthorizationSkipsPermissionChecks(): void
    {
        $this->actingAs($this->regularUser);

        $action = Action::create(['name' => 'Test Action', 'slug' => 'test.action', 'description' => 'Test']);
        $user = User::create(['name' => 'Test User', 'email' => 'test@test.com']);

        $this->service->withoutAuthorization()->assignAction($user, $action);

        $this->assertTrue($user->fresh()->actions->contains($action));
    }
}
