<?php

namespace DFiks\UnPerm\Tests\Feature;

use DFiks\UnPerm\Models\Group;
use DFiks\UnPerm\Tests\TestCase;

class SyncGroupsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'unperm.actions' => [
                'users' => ['view' => 'View'],
                'posts' => ['view' => 'View', 'edit' => 'Edit'],
            ],
            'unperm.roles' => [
                'editor' => [
                    'name' => 'Editor',
                    'actions' => ['posts.*'],
                ],
                'viewer' => [
                    'name' => 'Viewer',
                    'actions' => ['*.view'],
                ],
            ],
            'unperm.groups' => [
                'content-team' => [
                    'name' => 'Content Team',
                    'description' => 'Content management',
                    'roles' => ['editor'],
                    'actions' => ['users.view'],
                ],
                'read-only' => [
                    'name' => 'Read Only',
                    'roles' => ['viewer'],
                    'actions' => [],
                ],
            ],
        ]);

        $this->artisan('unperm:sync-actions');
        $this->artisan('unperm:sync-roles');
    }

    public function testSyncsGroupsFromConfig(): void
    {
        $this->artisan('unperm:sync-groups')
            ->assertSuccessful();

        $this->assertDatabaseHas('groups', ['slug' => 'content-team', 'name' => 'Content Team']);
        $this->assertDatabaseHas('groups', ['slug' => 'read-only', 'name' => 'Read Only']);

        $this->assertEquals(2, Group::count());
    }

    public function testAssignsRolesToGroups(): void
    {
        $this->artisan('unperm:sync-groups');

        $contentTeam = Group::where('slug', 'content-team')->first();
        $this->assertCount(1, $contentTeam->roles);
        $this->assertEquals('editor', $contentTeam->roles->first()->slug);

        $readOnly = Group::where('slug', 'read-only')->first();
        $this->assertCount(1, $readOnly->roles);
        $this->assertEquals('viewer', $readOnly->roles->first()->slug);
    }

    public function testAssignsActionsToGroups(): void
    {
        $this->artisan('unperm:sync-groups');

        $contentTeam = Group::where('slug', 'content-team')->first();
        $this->assertCount(1, $contentTeam->actions);
        $this->assertEquals('users.view', $contentTeam->actions->first()->slug);
    }

    public function testCalculatesBitmasks(): void
    {
        $this->artisan('unperm:sync-groups');

        $contentTeam = Group::where('slug', 'content-team')->first();
        $readOnly = Group::where('slug', 'read-only')->first();

        $this->assertNotEquals('0', $contentTeam->bitmask);
        $this->assertNotEquals('0', $readOnly->bitmask);
    }

    public function testFreshOptionDeletesExisting(): void
    {
        Group::create(['name' => 'Old Group', 'slug' => 'old-group', 'bitmask' => '0']);

        $this->artisan('unperm:sync-groups', ['--fresh' => true])
            ->assertSuccessful();

        $this->assertDatabaseMissing('groups', ['slug' => 'old-group']);
        $this->assertEquals(2, Group::count());
    }
}
