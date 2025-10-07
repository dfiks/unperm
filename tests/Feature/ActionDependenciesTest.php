<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Tests\Feature;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Tests\Models\User;
use DFiks\UnPerm\Tests\TestCase;

class ActionDependenciesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['unperm.cache.enabled' => false]);
        config([
            'unperm.actions' => [
                'posts' => [
                    'view' => [
                        'name' => 'View posts',
                    ],
                    'edit' => [
                        'name' => 'Edit posts',
                        'depends' => ['posts.view'],
                    ],
                ],
            ],
        ]);

        $this->artisan('unperm:sync-actions')->assertSuccessful();
    }

    public function testHasActionRespectsDependencies(): void
    {
        $user = User::create(['name' => 'U', 'email' => 'u@example.com']);

        $edit = Action::where('slug', 'posts.edit')->first();
        $view = Action::where('slug', 'posts.view')->first();

        $user->assignAction($edit);

        $this->assertFalse($user->hasAction('posts.edit'));

        $user->assignAction($view);

        $this->assertTrue($user->hasAction('posts.edit'));
    }

    public function testHasAllAndAnyWithDependencies(): void
    {
        $user = User::create(['name' => 'U', 'email' => 'u2@example.com']);

        $user->assignAction('posts.view');

        $this->assertTrue($user->hasAnyAction(['posts.edit', 'posts.view']));
        $this->assertFalse($user->hasAllActions(['posts.view', 'posts.edit']));

        $user->assignAction('posts.edit');

        $this->assertTrue($user->hasAllActions(['posts.view', 'posts.edit']));
    }
}
