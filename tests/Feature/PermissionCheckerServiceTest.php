<?php

namespace DFiks\UnPerm\Tests\Feature;

use DFiks\UnPerm\Facades\UnPerm;
use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Group;
use DFiks\UnPerm\Models\Role;
use DFiks\UnPerm\Services\PermissionChecker;
use DFiks\UnPerm\Tests\Models\User;
use DFiks\UnPerm\Tests\TestCase;

class PermissionCheckerServiceTest extends TestCase
{
    private PermissionChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->checker = app(PermissionChecker::class);
    }

    public function testChecksActionPermission(): void
    {
        $action = Action::create(['name' => 'View', 'slug' => 'view', 'bitmask' => '1']);

        $this->assertTrue($this->checker->checkActionPermission($action, 0));
        $this->assertFalse($this->checker->checkActionPermission($action, 1));
    }

    public function testChecksRoleHasActionViaBitmask(): void
    {
        $action = Action::create(['name' => 'Edit', 'slug' => 'edit', 'bitmask' => '2']);
        $role = Role::create(['name' => 'Editor', 'slug' => 'editor', 'bitmask' => '2']);

        $this->assertTrue($this->checker->checkRoleHasAction($role, $action));
    }

    public function testChecksRoleHasActionViaRelation(): void
    {
        $action = Action::create(['name' => 'Edit', 'slug' => 'edit', 'bitmask' => '2']);
        $role = Role::create(['name' => 'Editor', 'slug' => 'editor', 'bitmask' => '0']);
        $role->actions()->attach($action->id);

        $this->assertTrue($this->checker->checkRoleHasAction($role, $action));
    }

    public function testModelCanCheckDirectAction(): void
    {
        $user = User::create(['name' => 'Test', 'email' => 'test@test.com']);
        $action = Action::create(['name' => 'View', 'slug' => 'view', 'bitmask' => '1']);
        $user->assignAction($action);

        $this->assertTrue($this->checker->modelCan($user, $action));
        $this->assertTrue($this->checker->modelCan($user, 'view'));
    }

    public function testModelCanCheckThroughRole(): void
    {
        $user = User::create(['name' => 'Test', 'email' => 'test@test.com']);
        $action = Action::create(['name' => 'Edit', 'slug' => 'edit', 'bitmask' => '2']);
        $role = Role::create(['name' => 'Editor', 'slug' => 'editor', 'bitmask' => '0']);
        
        $role->actions()->attach($action->id);
        $user->assignRole($role);
        $user->load('roles');

        $this->assertTrue($this->checker->modelCan($user, $action));
    }

    public function testModelCanCheckThroughGroup(): void
    {
        $user = User::create(['name' => 'Test', 'email' => 'test@test.com']);
        $action = Action::create(['name' => 'Delete', 'slug' => 'delete', 'bitmask' => '4']);
        $group = Group::create(['name' => 'Team', 'slug' => 'team', 'bitmask' => '0']);
        
        $group->actions()->attach($action->id);
        $user->assignGroup($group);
        $user->load('groups');

        $this->assertTrue($this->checker->modelCan($user, $action));
    }

    public function testModelHasBit(): void
    {
        $user = User::create(['name' => 'Test', 'email' => 'test@test.com']);
        $action = Action::create(['name' => 'View', 'slug' => 'view', 'bitmask' => '1']);
        $user->assignAction($action);

        $this->assertTrue($this->checker->modelHasBit($user, 0));
        $this->assertFalse($this->checker->modelHasBit($user, 1));
    }

    public function testCanUseFacade(): void
    {
        $user = User::create(['name' => 'Test', 'email' => 'test@test.com']);
        $action = Action::create(['name' => 'View', 'slug' => 'view', 'bitmask' => '1']);
        $user->assignAction($action);

        $this->assertTrue(UnPerm::modelCan($user, $action));
        $this->assertTrue(UnPerm::modelHasBit($user, 0));
    }
}

