<?php

namespace DFiks\UnPerm\Tests\Unit;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Role;
use DFiks\UnPerm\Tests\TestCase;

class RoleModelTest extends TestCase
{
    public function testCreatesRoleWithUuid(): void
    {
        $role = Role::create([
            'name' => 'Editor',
            'slug' => 'editor',
            'bitmask' => '0',
        ]);

        $this->assertIsString($role->id);
        $this->assertDatabaseHas('roles', ['slug' => 'editor']);
    }

    public function testAttachesActions(): void
    {
        $role = Role::create(['name' => 'Editor', 'slug' => 'editor']);
        $action = Action::create(['name' => 'Edit', 'slug' => 'edit', 'bitmask' => '1']);

        $role->actions()->attach($action->id);

        $this->assertCount(1, $role->fresh()->actions);
    }

    public function testSyncsBitmaskFromActions(): void
    {
        $role = Role::create(['name' => 'Editor', 'slug' => 'editor', 'bitmask' => '0']);

        $action1 = Action::create(['name' => 'View', 'slug' => 'view', 'bitmask' => '1']);
        $action2 = Action::create(['name' => 'Edit', 'slug' => 'edit', 'bitmask' => '4']);

        $role->actions()->attach([$action1->id, $action2->id]);
        $role->load('actions')->syncBitmaskFromActions()->save();

        $this->assertEquals('5', $role->bitmask);
    }

    public function testBelongsToGroups(): void
    {
        $role = Role::create(['name' => 'Editor', 'slug' => 'editor']);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $role->groups());
    }
}
