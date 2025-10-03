<?php

namespace DFiks\UnPerm\Tests\Unit;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Group;
use DFiks\UnPerm\Models\Role;
use DFiks\UnPerm\Tests\TestCase;

class GroupModelTest extends TestCase
{
    public function testCreatesGroupWithUuid(): void
    {
        $group = Group::create([
            'name' => 'Content Team',
            'slug' => 'content-team',
            'bitmask' => '0',
        ]);

        $this->assertIsString($group->id);
        $this->assertDatabaseHas('groups', ['slug' => 'content-team']);
    }

    public function testAttachesRolesAndActions(): void
    {
        $group = Group::create(['name' => 'Team', 'slug' => 'team']);
        $role = Role::create(['name' => 'Editor', 'slug' => 'editor']);
        $action = Action::create(['name' => 'View', 'slug' => 'view', 'bitmask' => '1']);

        $group->roles()->attach($role->id);
        $group->actions()->attach($action->id);

        $group = $group->fresh();
        $this->assertCount(1, $group->roles);
        $this->assertCount(1, $group->actions);
    }

    public function testSyncsBitmaskFromRolesAndActions(): void
    {
        $group = Group::create(['name' => 'Team', 'slug' => 'team', 'bitmask' => '0']);

        $role = Role::create(['name' => 'Editor', 'slug' => 'editor', 'bitmask' => '3']);
        $action = Action::create(['name' => 'Delete', 'slug' => 'delete', 'bitmask' => '8']);

        $group->roles()->attach($role->id);
        $group->actions()->attach($action->id);
        $group->load(['roles', 'actions'])->syncBitmaskFromRolesAndActions()->save();

        $this->assertEquals('11', $group->bitmask);
    }
}
