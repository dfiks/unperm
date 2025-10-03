<?php

namespace DFiks\UnPerm\Tests\Feature;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Group;
use DFiks\UnPerm\Models\Role;
use DFiks\UnPerm\Support\PermBit;
use DFiks\UnPerm\Tests\Models\User;
use DFiks\UnPerm\Tests\TestCase;

class FullWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        config([
            'unperm.actions' => [
                'users' => [
                    'view' => 'Просмотр пользователей',
                    'create' => 'Создание пользователей',
                    'edit' => 'Редактирование пользователей',
                    'delete' => 'Удаление пользователей',
                ],
                'posts' => [
                    'view' => 'Просмотр постов',
                    'create' => 'Создание постов',
                    'edit' => 'Редактирование постов',
                    'delete' => 'Удаление постов',
                    'publish' => 'Публикация постов',
                ],
                'comments' => [
                    'view' => 'Просмотр комментариев',
                    'moderate' => 'Модерация комментариев',
                ],
            ],
            'unperm.roles' => [
                'editor' => [
                    'name' => 'Редактор',
                    'description' => 'Может создавать и редактировать посты',
                    'actions' => ['posts.view', 'posts.create', 'posts.edit'],
                ],
                'moderator' => [
                    'name' => 'Модератор',
                    'description' => 'Может модерировать комментарии',
                    'actions' => ['comments.view', 'comments.moderate'],
                ],
                'viewer' => [
                    'name' => 'Просмотрщик',
                    'description' => 'Может только просматривать',
                    'actions' => ['*.view'],
                ],
                'admin' => [
                    'name' => 'Администратор',
                    'description' => 'Полный доступ к пользователям',
                    'actions' => ['users.*'],
                ],
            ],
            'unperm.groups' => [
                'content-team' => [
                    'name' => 'Команда контента',
                    'description' => 'Работники контента',
                    'roles' => ['editor', 'moderator'],
                    'actions' => [],
                ],
                'super-users' => [
                    'name' => 'Супер пользователи',
                    'description' => 'Все права',
                    'roles' => ['viewer', 'editor', 'admin'],
                    'actions' => [],
                ],
            ],
        ]);
    }

    public function testCompleteWorkflow(): void
    {
        // 1. Синхронизируем всё из конфига одной командой
        $this->artisan('unperm:sync')->assertSuccessful();
        
        // Проверяем что всё создалось
        $this->assertEquals(11, Action::count());
        $this->assertEquals(4, Role::count());
        $this->assertEquals(2, Group::count());

        // 2. Получаем созданные из конфига объекты
        $editorRole = Role::where('slug', 'editor')->first();
        $moderatorRole = Role::where('slug', 'moderator')->first();
        $contentTeam = Group::where('slug', 'content-team')->first();

        // Проверяем что роли имеют правильные actions
        $this->assertCount(3, $editorRole->actions);
        $this->assertCount(2, $moderatorRole->actions);
        
        // Проверяем что группа имеет правильные роли
        $this->assertCount(2, $contentTeam->roles);
        $this->assertTrue($contentTeam->roles->contains($editorRole));
        $this->assertTrue($contentTeam->roles->contains($moderatorRole));

        // 3. Создаем пользователей
        $user1 = User::create(['name' => 'Иван', 'email' => 'ivan@test.com']);
        $user2 = User::create(['name' => 'Мария', 'email' => 'maria@test.com']);
        $user3 = User::create(['name' => 'Петр', 'email' => 'petr@test.com']);

        // 4. Назначаем разрешения пользователям
        
        // Иван - напрямую action
        $user1->assignAction('users.view');
        $user1->assignAction('users.create');

        // Мария - через роль
        $user2->assignRole($editorRole);

        // Петр - через группу
        $user3->assignGroup($contentTeam);

        // 5. Проверяем разрешения (все через битовые маски!)

        // Иван - прямые actions
        $this->assertTrue($user1->hasAction('users.view'));
        $this->assertTrue($user1->hasAction('users.create'));
        $this->assertFalse($user1->hasAction('posts.view'));

        // Мария - через роль Editor
        $this->assertTrue($user2->hasRole('editor'));
        $this->assertTrue($user2->hasAction('posts.view'));
        $this->assertTrue($user2->hasAction('posts.create'));
        $this->assertTrue($user2->hasAction('posts.edit'));
        $this->assertFalse($user2->hasAction('posts.delete'));
        $this->assertFalse($user2->hasAction('users.view'));

        // Петр - через группу (получает все разрешения группы)
        $this->assertTrue($user3->hasGroup('content-team'));
        $this->assertTrue($user3->hasAction('posts.view'));
        $this->assertTrue($user3->hasAction('posts.create'));
        $this->assertTrue($user3->hasAction('comments.moderate'));

        // 6. Проверяем битовые маски
        $user1Mask = $user1->getPermissionBitmask();
        $user2Mask = $user2->getPermissionBitmask();
        $user3Mask = $user3->getPermissionBitmask();

        // Проверяем через PermBit
        $user1Actions = PermBit::getActions($user1Mask);
        $this->assertCount(2, $user1Actions);
        $this->assertContains('users.view', $user1Actions);
        $this->assertContains('users.create', $user1Actions);

        $user2Actions = PermBit::getActions($user2Mask);
        $this->assertContains('posts.view', $user2Actions);
        $this->assertContains('posts.create', $user2Actions);
        $this->assertContains('posts.edit', $user2Actions);

        $user3Actions = PermBit::getActions($user3Mask);
        $this->assertGreaterThanOrEqual(5, count($user3Actions));
        $this->assertContains('posts.view', $user3Actions);
        $this->assertContains('comments.moderate', $user3Actions);

        // 7. Проверяем комбинированные проверки (через битовые маски)
        $this->assertTrue($user1->hasAnyAction(['users.view', 'users.delete']));
        $this->assertTrue($user1->hasAllActions(['users.view', 'users.create']));
        $this->assertFalse($user1->hasAllActions(['users.view', 'users.edit']));

        $this->assertTrue($user2->hasAnyAction(['posts.view', 'posts.publish']));
        $this->assertTrue($user2->hasAllActions(['posts.view', 'posts.create', 'posts.edit']));
        $this->assertFalse($user2->hasAllActions(['posts.view', 'posts.delete']));

        // 8. Проверяем работу PermBit напрямую
        $combinedMask = PermBit::combine([
            'users.view',
            'users.create',
            'posts.view',
            'posts.create',
            'comments.view',
        ]);

        $this->assertTrue(PermBit::hasAction($combinedMask, 'users.view'));
        $this->assertTrue(PermBit::hasAction($combinedMask, 'comments.view'));
        $this->assertFalse(PermBit::hasAction($combinedMask, 'posts.delete'));

        // 9. Обновляем разрешения
        $user1->syncActions(['users.edit', 'users.delete']);
        $user1 = $user1->fresh();

        $this->assertFalse($user1->hasAction('users.view'));
        $this->assertTrue($user1->hasAction('users.edit'));
        $this->assertTrue($user1->hasAction('users.delete'));
        
        // 10. Удаляем роль у пользователя
        $user2->removeRole($editorRole);
        $user2 = $user2->fresh();
        
        $this->assertFalse($user2->hasAction('posts.view'));
        
        // 11. Пересчитываем битовые маски командой
        $this->artisan('unperm:rebuild-bitmask')->assertSuccessful();
        
        $editorRole = $editorRole->fresh();
        $this->assertNotEquals('0', $editorRole->bitmask);
    }

    public function testUserCanCheckMultiplePermissions(): void
    {
        // Синхронизируем только actions
        $this->artisan('unperm:sync-actions')->assertSuccessful();

        $user = User::create(['name' => 'Test', 'email' => 'test@test.com']);
        
        // Назначаем несколько actions напрямую
        $user->assignActions([
            'users.view',
            'users.create',
            'posts.view',
        ]);

        // ВСЕ проверки работают через битовые маски!
        $this->assertTrue($user->hasAction('users.view'));
        $this->assertTrue($user->hasAnyAction(['users.edit', 'users.view']));
        $this->assertTrue($user->hasAllActions(['users.view', 'posts.view']));
        $this->assertFalse($user->hasAllActions(['users.view', 'posts.create']));
        
        // Проверка с несуществующими actions
        $this->assertTrue($user->hasAnyAction(['users.view', 'unknown.action']));
        $this->assertFalse($user->hasAllActions(['users.view', 'users.delete']));

        // Получаем список всех actions из битовой маски
        $mask = $user->getPermissionBitmask();
        $actions = PermBit::getActions($mask);
        
        $this->assertCount(3, $actions);
        $this->assertContains('users.view', $actions);
        $this->assertContains('users.create', $actions);
        $this->assertContains('posts.view', $actions);
    }

    public function testComplexHierarchy(): void
    {
        // Синхронизируем всё из конфига
        $this->artisan('unperm:sync')->assertSuccessful();

        // Создаем пользователя
        $user = User::create(['name' => 'SuperAdmin', 'email' => 'superadmin@test.com']);

        // Получаем группу super-users из конфига (содержит viewer, editor, admin роли)
        $superGroup = Group::where('slug', 'super-users')->first();
        
        // Проверяем что группа имеет все роли из конфига
        $this->assertCount(3, $superGroup->roles);
        $rolesSlugs = $superGroup->roles->pluck('slug')->toArray();
        $this->assertContains('viewer', $rolesSlugs);
        $this->assertContains('editor', $rolesSlugs);
        $this->assertContains('admin', $rolesSlugs);

        // Назначаем группу пользователю
        $user->assignGroup($superGroup);

        // Пользователь получает ВСЕ права из ВСЕХ ролей группы
        // viewer дает *.view
        $this->assertTrue($user->hasAction('users.view'));
        $this->assertTrue($user->hasAction('posts.view'));
        $this->assertTrue($user->hasAction('comments.view'));
        
        // editor дает posts.*
        $this->assertTrue($user->hasAction('posts.create'));
        $this->assertTrue($user->hasAction('posts.edit'));
        
        // admin дает users.*
        $this->assertTrue($user->hasAction('users.create'));
        $this->assertTrue($user->hasAction('users.edit'));
        $this->assertTrue($user->hasAction('users.delete'));

        // Получаем агрегированную битовую маску
        $mask = $user->getPermissionBitmask();
        $actions = PermBit::getActions($mask);

        // Должно быть много actions (из всех 3 ролей)
        $this->assertGreaterThan(7, count($actions));
        
        // Проверяем что все маски работают корректно
        $this->assertNotEquals('0', $mask);
    }
}

