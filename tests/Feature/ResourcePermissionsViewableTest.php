<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Tests\Feature;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\ResourceAction;
use DFiks\UnPerm\Support\ResourcePermission;
use DFiks\UnPerm\Tests\Models\Folder;
use DFiks\UnPerm\Tests\Models\User;
use DFiks\UnPerm\Tests\TestCase;

class ResourcePermissionsViewableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('unperm:sync')->assertSuccessful();
    }

    public function testUserCanSeeOnlyFoldersTheyHaveAccessTo(): void
    {
        // Создаем пользователя
        $user = User::create(['name' => 'John', 'email' => 'john@example.com']);
        $otherUser = User::create(['name' => 'Jane', 'email' => 'jane@example.com']);

        // Создаем папки
        $folder1 = Folder::create(['name' => 'John Folder 1']);
        $folder2 = Folder::create(['name' => 'John Folder 2']);
        $folder3 = Folder::create(['name' => 'Other Folder']);

        // Даем права пользователю John на первые две папки
        ResourcePermission::grant($user, $folder1, 'view');
        ResourcePermission::grant($user, $folder2, 'view');

        // Даем права пользователю Jane на третью папку
        ResourcePermission::grant($otherUser, $folder3, 'view');

        // Проверяем что ResourceActions созданы
        $this->assertEquals(3, ResourceAction::where('action_type', 'view')->count());

        // Проверяем что пользователь видит только свои папки через scope
        $viewableFolders = Folder::viewableBy($user)->get();

        dump([
            'user' => $user->name,
            'total_folders' => Folder::count(),
            'viewable_folders' => $viewableFolders->count(),
            'viewable_ids' => $viewableFolders->pluck('id')->toArray(),
            'expected_ids' => [$folder1->id, $folder2->id],
            'resource_actions' => ResourceAction::select('id', 'slug', 'action_type')->get()->toArray(),
            'user_resource_actions' => $user->resourceActions()->get(['id', 'slug'])->toArray(),
        ]);

        $this->assertEquals(
            2,
            $viewableFolders->count(),
            'User should see exactly 2 folders they have access to'
        );
        $this->assertTrue($viewableFolders->contains($folder1));
        $this->assertTrue($viewableFolders->contains($folder2));
        $this->assertFalse($viewableFolders->contains($folder3));
    }

    public function testUserCanCheckPermissionsOnSpecificFolder(): void
    {
        $user = User::create(['name' => 'John', 'email' => 'john@example.com']);
        $folder = Folder::create(['name' => 'Test Folder']);

        // Даем права на просмотр
        ResourcePermission::grant($user, $folder, 'view');

        // Проверяем что пользователь может просматривать папку
        $this->assertTrue(
            $folder->userCan($user, 'view'),
            'User should be able to view folder'
        );
        $this->assertFalse(
            $folder->userCan($user, 'edit'),
            'User should NOT be able to edit folder'
        );
    }

    public function testResourceActionsAreFoundBySlugPattern(): void
    {
        // Создаем папку и пользователя
        $user = User::create(['name' => 'John', 'email' => 'john@example.com']);
        $folder = Folder::create(['name' => 'Test Folder']);

        // Даем права
        ResourcePermission::grant($user, $folder, 'view');

        // Получаем созданный ResourceAction
        $resourceAction = ResourceAction::where('resource_id', $folder->id)
            ->where('action_type', 'view')
            ->first();

        $this->assertNotNull($resourceAction, 'ResourceAction should be created');

        // Проверяем формат slug
        $expectedSlug = 'folders.view.' . $folder->id;
        $this->assertEquals(
            $expectedSlug,
            $resourceAction->slug,
            "ResourceAction slug should be '{$expectedSlug}'"
        );

        // Проверяем что можем найти по паттерну
        $foundByPattern = ResourceAction::where('slug', 'like', 'folders.view.%')->get();
        $this->assertGreaterThan(
            0,
            $foundByPattern->count(),
            'Should find ResourceActions by pattern folders.view.%'
        );
        $this->assertTrue($foundByPattern->contains($resourceAction));
    }

    public function testGlobalActionLinkingWithResourceActions(): void
    {
        // Создаем глобальный action
        $globalAction = Action::create([
            'name' => 'View Folders',
            'slug' => 'folders.view',
            'bitmask' => '0',
        ]);

        // Создаем папки и даем права
        $user = User::create(['name' => 'John', 'email' => 'john@example.com']);
        $folder1 = Folder::create(['name' => 'Folder 1']);
        $folder2 = Folder::create(['name' => 'Folder 2']);

        ResourcePermission::grant($user, $folder1, 'view');
        ResourcePermission::grant($user, $folder2, 'view');

        // Проверяем что можем найти resource actions под глобальным action
        $resourceActions = ResourceAction::where('slug', 'like', $globalAction->slug . '.%')->get();

        dump([
            'global_action_slug' => $globalAction->slug,
            'pattern' => $globalAction->slug . '.%',
            'found_resource_actions' => $resourceActions->count(),
            'slugs' => $resourceActions->pluck('slug')->toArray(),
        ]);

        $this->assertEquals(
            2,
            $resourceActions->count(),
            'Should find 2 ResourceActions under global action'
        );
    }
}
