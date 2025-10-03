<?php

namespace DFiks\UnPerm\Tests\Feature;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Group;
use DFiks\UnPerm\Models\Role;
use DFiks\UnPerm\Tests\Models\User;
use DFiks\UnPerm\Tests\TestCase;

class HasPermissionsTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@test.com',
        ]);
    }

    public function testAssignsAndChecksAction(): void
    {
        $action = Action::create(['name' => 'View', 'slug' => 'view', 'bitmask' => '1']);

        $this->user->assignAction($action);

        $this->assertTrue($this->user->hasAction($action));
        $this->assertTrue($this->user->hasAction('view'));
    }

    public function testAssignsActionBySlug(): void
    {
        Action::create(['name' => 'Create', 'slug' => 'create', 'bitmask' => '2']);

        $this->user->assignAction('create');

        $this->assertTrue($this->user->hasAction('create'));
    }

    public function testAssignsMultipleActions(): void
    {
        Action::create(['name' => 'View', 'slug' => 'view', 'bitmask' => '1']);
        Action::create(['name' => 'Edit', 'slug' => 'edit', 'bitmask' => '2']);

        $this->user->assignActions(['view', 'edit']);

        $this->assertCount(2, $this->user->actions);
    }

    public function testRemovesAction(): void
    {
        $action = Action::create(['name' => 'Delete', 'slug' => 'delete', 'bitmask' => '4']);
        $this->user->assignAction($action);

        $this->user->removeAction($action);

        $this->assertFalse($this->user->fresh()->hasAction($action));
    }

    public function testSyncsActions(): void
    {
        Action::create(['name' => 'View', 'slug' => 'view', 'bitmask' => '1']);
        Action::create(['name' => 'Edit', 'slug' => 'edit', 'bitmask' => '2']);
        Action::create(['name' => 'Delete', 'slug' => 'delete', 'bitmask' => '4']);

        $this->user->syncActions(['view', 'edit']);
        $this->assertCount(2, $this->user->fresh()->actions);

        $this->user->syncActions(['edit', 'delete']);
        $this->user = $this->user->fresh();

        $this->assertFalse($this->user->hasAction('view'));
        $this->assertTrue($this->user->hasAction('edit'));
        $this->assertTrue($this->user->hasAction('delete'));
    }

    public function testAssignsAndChecksRole(): void
    {
        $role = Role::create(['name' => 'Editor', 'slug' => 'editor']);

        $this->user->assignRole($role);

        $this->assertTrue($this->user->hasRole($role));
        $this->assertTrue($this->user->hasRole('editor'));
    }

    public function testAssignsAndChecksGroup(): void
    {
        $group = Group::create(['name' => 'Team', 'slug' => 'team']);

        $this->user->assignGroup($group);

        $this->assertTrue($this->user->hasGroup($group));
        $this->assertTrue($this->user->hasGroup('team'));
    }

    public function testChecksAnyAction(): void
    {
        Action::create(['name' => 'View', 'slug' => 'view', 'bitmask' => '1']);
        Action::create(['name' => 'Edit', 'slug' => 'edit', 'bitmask' => '2']);

        $this->user->assignAction('edit');

        $this->assertTrue($this->user->hasAnyAction(['view', 'edit']));
        $this->assertFalse($this->user->hasAnyAction(['view']));
    }

    public function testChecksAllActions(): void
    {
        Action::create(['name' => 'View', 'slug' => 'view', 'bitmask' => '1']);
        Action::create(['name' => 'Edit', 'slug' => 'edit', 'bitmask' => '2']);

        $this->user->assignActions(['view', 'edit']);

        $this->assertTrue($this->user->hasAllActions(['view', 'edit']));
        $this->assertFalse($this->user->hasAllActions(['view', 'edit', 'delete']));
    }

    public function testGetsAggregatedBitmask(): void
    {
        $action = Action::create(['name' => 'View', 'slug' => 'view', 'bitmask' => '1']);
        $role = Role::create(['name' => 'Editor', 'slug' => 'editor', 'bitmask' => '2']);
        $group = Group::create(['name' => 'Team', 'slug' => 'team', 'bitmask' => '4']);

        $this->user->assignAction($action);
        $this->user->assignRole($role);
        $this->user->assignGroup($group);

        $mask = $this->user->getPermissionBitmask();

        $this->assertEquals('7', $mask);
    }

    public function testChecksPermissionBit(): void
    {
        $action = Action::create(['name' => 'View', 'slug' => 'view', 'bitmask' => '1']);
        $this->user->assignAction($action);

        $this->assertTrue($this->user->hasPermissionBit(0));
        $this->assertFalse($this->user->hasPermissionBit(1));
    }

    public function testPreventsDuplicateActions(): void
    {
        $action = Action::create(['name' => 'View', 'slug' => 'view', 'bitmask' => '1']);

        $this->user->assignAction($action);
        $this->user->assignAction($action);

        $this->assertCount(1, $this->user->fresh()->actions);
    }
}
