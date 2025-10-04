<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Tests\Feature;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\ResourceAction;
use DFiks\UnPerm\Support\ResourcePermission;
use DFiks\UnPerm\Tests\Models\Folder;
use DFiks\UnPerm\Tests\Models\User;
use DFiks\UnPerm\Tests\TestCase;

class ManageActionsResourceActionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('unperm:sync')->assertSuccessful();
    }

    public function testResourceActionCreatedWithoutGlobalAction(): void
    {
        // Создаем пользователя и папку
        $user = User::create(['name' => 'John', 'email' => 'john@example.com']);
        $folder = Folder::create(['name' => 'Test Folder']);

        // Удаляем глобальный action если он есть
        Action::where('slug', 'folders.view')->delete();

        // Назначаем права на конкретную папку (это создаст ResourceAction даже без глобального action)
        ResourcePermission::grant($user, $folder, 'view');

        // Проверяем что ResourceAction создан
        $this->assertDatabaseHas('resource_actions', [
            'resource_type' => Folder::class,
            'action_type' => 'view',
        ]);

        // Проверяем что нет глобального action
        $this->assertDatabaseMissing('actions', [
            'slug' => 'folders.view',
        ]);

        // Проверяем что можем найти orphaned resource actions
        $orphaned = ResourceAction::selectRaw('resource_type, action_type, COUNT(*) as count')
            ->groupBy('resource_type', 'action_type')
            ->havingRaw('COUNT(*) > 0')
            ->get()
            ->filter(function ($group) {
                $expectedSlug = 'folders.' . $group->action_type;

                return !Action::where('slug', $expectedSlug)->exists();
            });

        $this->assertTrue($orphaned->count() > 0, 'Should find orphaned resource actions');
    }

    public function testCanCreateGlobalActionManually(): void
    {
        // Удаляем глобальный action если он есть
        Action::where('slug', 'folders.view')->delete();

        // Создаем глобальный action вручную
        $action = Action::create([
            'name' => 'View Folders',
            'slug' => 'folders.view',
            'bitmask' => '0',
        ]);

        // Проверяем что глобальный action создан
        $this->assertDatabaseHas('actions', [
            'slug' => 'folders.view',
        ]);

        $this->assertNotNull($action);
        $this->assertEquals('folders.view', $action->slug);
    }

    public function testFindsResourceActionsUnderGlobalAction(): void
    {
        // Создаем глобальный action
        $action = Action::create([
            'name' => 'View Folders',
            'slug' => 'folders.view',
            'bitmask' => '0',
        ]);

        // Создаем пользователя и несколько папок
        $user = User::create(['name' => 'John', 'email' => 'john@example.com']);
        $folder1 = Folder::create(['name' => 'Folder 1']);
        $folder2 = Folder::create(['name' => 'Folder 2']);

        // Назначаем права на конкретные папки
        ResourcePermission::grant($user, $folder1, 'view');
        ResourcePermission::grant($user, $folder2, 'view');

        // Проверяем что ResourceActions созданы
        $this->assertEquals(2, ResourceAction::where('action_type', 'view')
            ->where('resource_type', Folder::class)
            ->count());

        // Проверяем что можем найти resource actions по паттерну глобального action
        $resourceActions = ResourceAction::where('slug', 'like', $action->slug . '.%')->get();
        $this->assertEquals(2, $resourceActions->count(), 'Should find 2 resource actions under global action');

        // Проверяем что у каждого правильный slug
        foreach ($resourceActions as $ra) {
            $this->assertStringStartsWith('folders.view.', $ra->slug);
            $this->assertEquals('view', $ra->action_type);
            $this->assertEquals(Folder::class, $ra->resource_type);
        }
    }

    public function testResourceActionsSlugsMatchGlobalActionSlug(): void
    {
        // Создаем папку
        $folder = Folder::create(['name' => 'Test Folder']);

        // Создаем ResourceAction
        $resourceAction = ResourceAction::findOrCreateForResource($folder, 'view');

        // Проверяем формат slug
        $this->assertEquals('folders.view.' . $folder->id, $resourceAction->slug);

        // Создаем глобальный action
        $action = Action::create([
            'name' => 'View Folders',
            'slug' => 'folders.view',
            'bitmask' => '0',
        ]);

        // Проверяем что ResourceAction найдется по паттерну
        $found = ResourceAction::where('slug', 'like', $action->slug . '.%')->exists();
        $this->assertTrue($found, 'ResourceAction должен находиться по паттерну глобального action');
    }

    public function testMultipleResourceActionsWithDifferentActionTypes(): void
    {
        $user = User::create(['name' => 'John', 'email' => 'john@example.com']);
        $folder = Folder::create(['name' => 'Test Folder']);

        // Назначаем разные права на одну папку
        ResourcePermission::grant($user, $folder, 'view');
        ResourcePermission::grant($user, $folder, 'edit');
        ResourcePermission::grant($user, $folder, 'delete');

        // Проверяем что созданы 3 разных ResourceAction
        $resourceActions = ResourceAction::where('resource_id', $folder->id)
            ->where('resource_type', Folder::class)
            ->get();

        $this->assertEquals(3, $resourceActions->count());
        $this->assertTrue($resourceActions->contains('action_type', 'view'));
        $this->assertTrue($resourceActions->contains('action_type', 'edit'));
        $this->assertTrue($resourceActions->contains('action_type', 'delete'));
    }
}
