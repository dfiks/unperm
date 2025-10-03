<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Tests\Feature;

use DFiks\UnPerm\Support\ResourcePermission;
use DFiks\UnPerm\Tests\Models\Folder;
use DFiks\UnPerm\Tests\Models\User;
use DFiks\UnPerm\Tests\TestCase;

class ResourcePermissionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Отключаем кеширование для тестов
        config(['unperm.cache.enabled' => false]);

        $this->artisan('unperm:sync')->assertSuccessful();
    }

    public function testGrantsPermissionToSpecificResource(): void
    {
        $user = User::create(['name' => 'John', 'email' => 'john@example.com']);
        $folder = Folder::create(['name' => 'Secret Folder']);

        // Назначаем право на просмотр конкретной папки
        ResourcePermission::grant($user, $folder, 'view');

        $this->assertTrue($folder->userCan($user, 'view'));
        $this->assertFalse($folder->userCan($user, 'edit'));
        $this->assertFalse($folder->userCan($user, 'delete'));
    }

    public function testRevokesPermissionFromSpecificResource(): void
    {
        $user = User::create(['name' => 'John', 'email' => 'john@example.com']);
        $folder = Folder::create(['name' => 'Secret Folder']);

        ResourcePermission::grant($user, $folder, 'view');
        $this->assertTrue($folder->userCan($user, 'view'));

        ResourcePermission::revoke($user, $folder, 'view');
        $this->assertFalse($folder->userCan($user, 'view'));
    }

    public function testGrantsCrudPermissions(): void
    {
        $user = User::create(['name' => 'John', 'email' => 'john@example.com']);
        $folder = Folder::create(['name' => 'Secret Folder']);

        ResourcePermission::grantCrud($user, $folder);

        $this->assertTrue($folder->userCan($user, 'view'));
        $this->assertTrue($folder->userCan($user, 'edit'));
        $this->assertTrue($folder->userCan($user, 'delete'));
    }

    public function testFiltersResourcesByPermissions(): void
    {
        $user = User::create(['name' => 'John', 'email' => 'john@example.com']);

        $folder1 = Folder::create(['name' => 'Folder 1']);
        $folder2 = Folder::create(['name' => 'Folder 2']);
        $folder3 = Folder::create(['name' => 'Folder 3']);

        // Назначаем права только на folder1 и folder2
        ResourcePermission::grant($user, $folder1, 'view');
        ResourcePermission::grant($user, $folder2, 'view');

        // Проверяем scope
        $viewableFolders = Folder::viewableBy($user)->get();

        $this->assertCount(2, $viewableFolders);
        $this->assertTrue($viewableFolders->contains('id', $folder1->id));
        $this->assertTrue($viewableFolders->contains('id', $folder2->id));
        $this->assertFalse($viewableFolders->contains('id', $folder3->id));
    }

    public function testWildcardPermissionGrantsAccessToAll(): void
    {
        $user = User::create(['name' => 'Admin', 'email' => 'admin@example.com']);

        $folder1 = Folder::create(['name' => 'Folder 1']);
        $folder2 = Folder::create(['name' => 'Folder 2']);

        // Создаем wildcard action если его нет
        $wildcardAction = \DFiks\UnPerm\Models\Action::firstOrCreate(
            ['slug' => 'folders.view'],
            ['name' => 'View all folders', 'bitmask' => '0']
        );
        \DFiks\UnPerm\Support\PermBit::rebuild();

        // Назначаем wildcard разрешение на просмотр всех папок
        $user->assignAction($wildcardAction);

        // Проверяем что есть доступ ко всем папкам
        $this->assertTrue($folder1->userCan($user, 'view'));
        $this->assertTrue($folder2->userCan($user, 'view'));

        // И scope возвращает все папки
        $viewableFolders = Folder::viewableBy($user)->get();
        $this->assertCount(2, $viewableFolders);
    }

    public function testFullWildcardGrantsAllActions(): void
    {
        $user = User::create(['name' => 'Superadmin', 'email' => 'super@example.com']);
        $folder = Folder::create(['name' => 'Important Folder']);

        // Создаем wildcard action если его нет
        $wildcardAction = \DFiks\UnPerm\Models\Action::firstOrCreate(
            ['slug' => 'folders.*'],
            ['name' => 'All actions on folders', 'bitmask' => '0']
        );
        \DFiks\UnPerm\Support\PermBit::rebuild();

        // Назначаем полный wildcard на все действия с папками
        $user->assignAction($wildcardAction);

        $this->assertTrue($folder->userCan($user, 'view'));
        $this->assertTrue($folder->userCan($user, 'edit'));
        $this->assertTrue($folder->userCan($user, 'delete'));
        $this->assertTrue($folder->userCan($user, 'any-custom-action'));
    }

    public function testReturnsEmptyResultWhenNoPermissions(): void
    {
        $user = User::create(['name' => 'Restricted', 'email' => 'restricted@example.com']);

        Folder::create(['name' => 'Folder 1']);
        Folder::create(['name' => 'Folder 2']);

        // Пользователь без прав не должен видеть ничего
        $viewableFolders = Folder::viewableBy($user)->get();
        $this->assertCount(0, $viewableFolders);
    }

    public function testRevokesAllPermissionsForResource(): void
    {
        $user = User::create(['name' => 'John', 'email' => 'john@example.com']);
        $folder = Folder::create(['name' => 'Temp Folder']);

        // Назначаем несколько прав
        ResourcePermission::grantCrud($user, $folder);

        $this->assertTrue($folder->userCan($user, 'view'));
        $this->assertTrue($folder->userCan($user, 'edit'));

        // Отзываем все права
        ResourcePermission::revokeAll($user, $folder);

        $this->assertFalse($folder->userCan($user, 'view'));
        $this->assertFalse($folder->userCan($user, 'edit'));
        $this->assertFalse($folder->userCan($user, 'delete'));
    }

    public function testGrantsToMultipleUsers(): void
    {
        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);
        $folder = Folder::create(['name' => 'Shared Folder']);

        ResourcePermission::grantToMany([$user1, $user2], $folder, ['view', 'edit']);

        $this->assertTrue($folder->userCan($user1, 'view'));
        $this->assertTrue($folder->userCan($user1, 'edit'));
        $this->assertTrue($folder->userCan($user2, 'view'));
        $this->assertTrue($folder->userCan($user2, 'edit'));
    }

    public function testGetsUsersWithAccess(): void
    {
        $user1 = User::create(['name' => 'User 1', 'email' => 'user1@example.com']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'user2@example.com']);
        $user3 = User::create(['name' => 'User 3', 'email' => 'user3@example.com']);
        $folder = Folder::create(['name' => 'Restricted Folder']);

        ResourcePermission::grant($user1, $folder, 'view');
        ResourcePermission::grant($user2, $folder, 'view');

        $usersWithAccess = ResourcePermission::getUsersWithAccess($folder, 'view', User::class);

        $this->assertCount(2, $usersWithAccess);
        $this->assertTrue($usersWithAccess->contains('id', $user1->id));
        $this->assertTrue($usersWithAccess->contains('id', $user2->id));
        $this->assertFalse($usersWithAccess->contains('id', $user3->id));
    }

    public function testEditableAndDeletableScopes(): void
    {
        $user = User::create(['name' => 'Editor', 'email' => 'editor@example.com']);

        $folder1 = Folder::create(['name' => 'Editable']);
        $folder2 = Folder::create(['name' => 'Deletable']);
        $folder3 = Folder::create(['name' => 'Read-only']);

        ResourcePermission::grant($user, $folder1, 'edit');
        ResourcePermission::grant($user, $folder2, 'delete');
        ResourcePermission::grant($user, $folder3, 'view');

        $editableFolders = Folder::editableBy($user)->get();
        $deletableFolders = Folder::deletableBy($user)->get();

        $this->assertCount(1, $editableFolders);
        $this->assertTrue($editableFolders->contains('id', $folder1->id));

        $this->assertCount(1, $deletableFolders);
        $this->assertTrue($deletableFolders->contains('id', $folder2->id));
    }
}
