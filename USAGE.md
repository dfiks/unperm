# UnPerm - Laravel Permission Package

Минимальный, но мощный пакет для управления разрешениями в Laravel с использованием битовых масок через GMP для максимальной производительности.

## Возможности

- ✅ **Actions, Roles, Groups** - полная иерархия разрешений
- ✅ **UUID** для всех ID
- ✅ **Битовые маски (GMP)** - мгновенная проверка прав
- ✅ **Полиморфные отношения** - назначение прав любой модели
- ✅ **Конфигурация через файл** - actions, roles, groups в `unperm.php`
- ✅ **Wildcard паттерны** - `users.*`, `*.view`
- ✅ **IDE Helper** - автодополнение для всех разрешений
- ✅ **75+ тестов** - полное покрытие

## Установка

```bash
composer require dfiks/unperm
```

## Быстрый старт

### 1. Настройка конфига

```php
// config/unperm.php
return [
    'actions' => [
        'users' => [
            'view' => 'Просмотр пользователей',
            'create' => 'Создание пользователей',
            'edit' => 'Редактирование пользователей',
        ],
        'posts' => [
            'view' => 'Просмотр постов',
            'create' => 'Создание постов',
        ],
    ],
    
    'roles' => [
        'admin' => [
            'name' => 'Администратор',
            'actions' => ['users.*', 'posts.*'],
        ],
        'editor' => [
            'name' => 'Редактор',
            'actions' => ['posts.*'],
        ],
    ],
    
    'groups' => [
        'content-team' => [
            'name' => 'Команда контента',
            'roles' => ['editor'],
        ],
    ],
];
```

### 2. Синхронизация

```bash
# Синхронизировать всё из конфига
php artisan unperm:sync

# Или по отдельности
php artisan unperm:sync-actions
php artisan unperm:sync-roles
php artisan unperm:sync-groups
```

### 3. Использование в модели

```php
use DFiks\UnPerm\Traits\HasPermissions;

class User extends Model
{
    use HasPermissions;
}
```

### 4. Работа с разрешениями

```php
$user = User::find(1);

// Назначение прав (напрямую action)
$user->assignAction('users.view');
$user->assignActions(['users.view', 'users.create']);

// Назначение через роль
$user->assignRole('admin');  // или Role::where('slug', 'admin')->first()

// Назначение через группу
$user->assignGroup('content-team');

// Проверка прав (ВСЕ через битовые маски!)
$user->hasAction('users.view');           // true/false
$user->hasAnyAction(['users.view', 'posts.create']);  // true если хоть одно
$user->hasAllActions(['users.view', 'users.create']); // true если все

// Проверка ролей и групп
$user->hasRole('admin');
$user->hasGroup('content-team');

// Удаление
$user->removeAction('users.view');
$user->removeRole('admin');

// Синхронизация (заменяет все)
$user->syncActions(['users.view', 'users.edit']);
```

## IDE Helper

Генерируйте файл для автодополнения:

```bash
php artisan unperm:generate-ide-helper
```

Это создаст файл `_ide_helper_permissions.php` с:
- Методами для всех actions/roles/groups
- Константами для строгой типизации

```php
// Теперь в IDE будет автодополнение:
$user->hasAction_users_view();  // автогенерированный метод

// Или используйте константы:
$user->hasAction(UnPermActions::USERS_VIEW);
```

## Команды

```bash
# Синхронизация
php artisan unperm:sync                    # Всё сразу
php artisan unperm:sync --fresh            # С очисткой БД
php artisan unperm:sync-actions            # Только actions
php artisan unperm:sync-roles              # Только roles
php artisan unperm:sync-groups             # Только groups

# Пересчёт битовых масок
php artisan unperm:rebuild-bitmask         # Пересчитать все маски

# IDE Helper
php artisan unperm:generate-ide-helper    # Генерация помощника
```

## Wildcard паттерны

```php
'actions' => ['users.*', 'posts.*', '*.view']
```

- `users.*` - все actions пользователей
- `*.view` - все view actions
- Точное совпадение - `users.create`

## Производительность

Все проверки `hasAction()`, `hasAnyAction()`, `hasAllActions()` работают через битовые маски:

```php
// Вместо запроса к БД:
SELECT * FROM model_actions WHERE model_id = ? AND action_id IN (...)

// Делается битовая операция:
(bitmask & action_mask) == action_mask  // O(1)
```

Это означает **мгновенные проверки** без запросов к БД!

## Использование PermBit

```php
use DFiks\UnPerm\Support\PermBit;

// Получить битовую маску для actions
$mask = PermBit::combine(['users.view', 'posts.create']);

// Проверить наличие прав
PermBit::hasAction($mask, 'users.view');  // true
PermBit::hasAllActions($mask, ['users.view', 'posts.create']);  // true

// Получить список actions из маски
$actions = PermBit::getActions($mask);  // ['users.view', 'posts.create']
```

## Фасад

```php
use DFiks\UnPerm\Facades\UnPerm;

UnPerm::checkActionPermission($user, 'users.view');
UnPerm::modelCan($user, 'posts.create');
```

## Тестирование

```bash
vendor/bin/phpunit
vendor/bin/phpunit --filter FullWorkflow
```

## Лицензия

MIT
