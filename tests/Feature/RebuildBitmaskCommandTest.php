<?php

namespace DFiks\UnPerm\Tests\Feature;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Group;
use DFiks\UnPerm\Models\Role;
use DFiks\UnPerm\Tests\TestCase;

class RebuildBitmaskCommandTest extends TestCase
{
    public function testRebuildsRoleBitmasks(): void
    {
        $role = Role::create(['name' => 'Editor', 'slug' => 'editor', 'bitmask' => '0']);
        $action1 = Action::create(['name' => 'View', 'slug' => 'view', 'bitmask' => '1']);
        $action2 = Action::create(['name' => 'Edit', 'slug' => 'edit', 'bitmask' => '2']);

        $role->actions()->attach([$action1->id, $action2->id]);

        $this->artisan('unperm:rebuild-bitmask', ['--roles' => true])
            ->assertSuccessful();

        $this->assertEquals('3', $role->fresh()->bitmask);
    }

    public function testRebuildsGroupBitmasks(): void
    {
        $group = Group::create(['name' => 'Team', 'slug' => 'team', 'bitmask' => '0']);
        $role = Role::create(['name' => 'Editor', 'slug' => 'editor', 'bitmask' => '2']);
        $action = Action::create(['name' => 'Delete', 'slug' => 'delete', 'bitmask' => '4']);

        $group->roles()->attach($role->id);
        $group->actions()->attach($action->id);

        $this->artisan('unperm:rebuild-bitmask', ['--groups' => true])
            ->assertSuccessful();

        $this->assertEquals('6', $group->fresh()->bitmask);
    }

    public function testRebuildsAllByDefault(): void
    {
        $role = Role::create(['name' => 'Editor', 'slug' => 'editor', 'bitmask' => '0']);
        $group = Group::create(['name' => 'Team', 'slug' => 'team', 'bitmask' => '0']);
        $action = Action::create(['name' => 'View', 'slug' => 'view', 'bitmask' => '1']);

        $role->actions()->attach($action->id);
        $group->roles()->attach($role->id);

        $this->artisan('unperm:rebuild-bitmask')
            ->assertSuccessful();

        $this->assertEquals('1', $role->fresh()->bitmask);
        $this->assertEquals('1', $group->fresh()->bitmask);
    }
}
