<?php

namespace DFiks\UnPerm\Tests\Feature;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Tests\TestCase;

class SyncActionsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'unperm.actions' => [
                'users' => [
                    'view' => 'View users',
                    'create' => 'Create users',
                ],
                'posts' => [
                    'view' => 'View posts',
                ],
            ],
        ]);
    }

    public function testSyncsActionsFromConfig(): void
    {
        $this->artisan('unperm:sync-actions')
            ->assertSuccessful();

        $this->assertDatabaseHas('actions', ['slug' => 'users.view']);
        $this->assertDatabaseHas('actions', ['slug' => 'users.create']);
        $this->assertDatabaseHas('actions', ['slug' => 'posts.view']);

        $this->assertEquals(3, Action::count());
    }

    public function testGeneratesUniqueBitmasks(): void
    {
        $this->artisan('unperm:sync-actions');

        $usersView = Action::where('slug', 'users.view')->first();
        $usersCreate = Action::where('slug', 'users.create')->first();
        $postsView = Action::where('slug', 'posts.view')->first();

        $this->assertEquals('1', $usersView->bitmask);
        $this->assertEquals('2', $usersCreate->bitmask);
        $this->assertEquals('4', $postsView->bitmask);
    }

    public function testFreshOptionDeletesExistingActions(): void
    {
        Action::create(['name' => 'Old', 'slug' => 'old', 'bitmask' => '999']);

        $this->artisan('unperm:sync-actions', ['--fresh' => true])
            ->assertSuccessful();

        $this->assertDatabaseMissing('actions', ['slug' => 'old']);
        $this->assertEquals(3, Action::count());
    }

    public function testUpdatesExistingActions(): void
    {
        Action::create(['name' => 'Old Name', 'slug' => 'users.view', 'bitmask' => '999']);

        $this->artisan('unperm:sync-actions');

        $action = Action::where('slug', 'users.view')->first();
        $this->assertEquals('View users', $action->name);
        $this->assertEquals('1', $action->bitmask);
    }

    public function testRemovesOrphanedActions(): void
    {
        $this->artisan('unperm:sync-actions');
        Action::create(['name' => 'Orphan', 'slug' => 'orphan', 'bitmask' => '999']);

        $this->assertEquals(4, Action::count());

        $this->artisan('unperm:sync-actions');

        $this->assertDatabaseMissing('actions', ['slug' => 'orphan']);
        $this->assertEquals(3, Action::count());
    }
}
