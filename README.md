# UnPerm - Laravel Permissions Package

Пакет для управления разрешениями в Laravel с поддержкой UUID и битовых масок для быстрой проверки прав доступа.

## Установка

```bash
composer require dfiks/unperm
```

Опубликуйте конфигурацию и миграции:

```bash
php artisan vendor:publish --tag=unperm-config
php artisan vendor:publish --tag=unperm-migrations
```

Выполните миграции:

```bash
php artisan migrate
```

## Базовое использование

### Добавление trait к модели

Добавьте trait `HasPermissions` к любой модели (например, User):

```php
use DFiks\UnPerm\Traits\HasPermissions;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasPermissions;
    
    // ...
}
```

### Создание Actions, Roles и Groups

```php
use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Role;
use DFiks\UnPerm\Models\Group;

// Создание действий
$createAction = Action::create([
    'name' => 'Create Post',
    'slug' => 'create-post',
    'description' => 'Can create posts',
    'bitmask' => 1 << 0, // бит 0
]);

$editAction = Action::create([
    'name' => 'Edit Post',
    'slug' => 'edit-post',
    'description' => 'Can edit posts',
    'bitmask' => 1 << 1, // бит 1
]);

// Создание роли
$editorRole = Role::create([
    'name' => 'Editor',
    'slug' => 'editor',
    'description' => 'Content editor role',
]);

// Назначение действий роли
$editorRole->actions()->attach([$createAction->id, $editAction->id]);

// Синхронизация битовой маски роли
$editorRole->syncBitmaskFromActions()->save();

// Создание группы
$contentGroup = Group::create([
    'name' => 'Content Managers',
    'slug' => 'content-managers',
    'description' => 'Content management group',
]);

// Назначение ролей группе
$contentGroup->roles()->attach($editorRole->id);

// Синхронизация битовой маски группы
$contentGroup->syncBitmaskFromRolesAndActions()->save();
```

### Назначение разрешений пользователю

```php
$user = User::find(1);

// Назначение действия
$user->assignAction('create-post');
$user->assignAction($createAction);

// Назначение нескольких действий
$user->assignActions(['create-post', 'edit-post']);

// Назначение роли
$user->assignRole('editor');
$user->assignRole($editorRole);

// Назначение группы
$user->assignGroup('content-managers');
$user->assignGroup($contentGroup);

// Синхронизация (заменяет все текущие)
$user->syncActions(['create-post', 'edit-post']);
$user->syncRoles(['editor']);
$user->syncGroups(['content-managers']);

// Удаление
$user->removeAction('create-post');
$user->removeRole('editor');
$user->removeGroup('content-managers');
```

### Проверка разрешений

```php
// Проверка действия
if ($user->hasAction('create-post')) {
    // Пользователь может создавать посты
}

// Проверка роли
if ($user->hasRole('editor')) {
    // Пользователь имеет роль редактора
}

// Проверка группы
if ($user->hasGroup('content-managers')) {
    // Пользователь в группе менеджеров контента
}

// Проверка любого из действий
if ($user->hasAnyAction(['create-post', 'edit-post'])) {
    // Пользователь может создавать или редактировать посты
}

// Проверка всех действий
if ($user->hasAllActions(['create-post', 'edit-post'])) {
    // Пользователь может и создавать, и редактировать посты
}
```

### Использование битовых масок

```php
// Быстрая проверка через битовую маску
if ($user->hasPermissionBit(0)) {
    // У пользователя есть разрешение с битом 0
}

// Проверка полной битовой маски
$requiredMask = (1 << 0) | (1 << 1); // биты 0 и 1
if ($user->hasPermissionBitmask($requiredMask)) {
    // У пользователя есть все требуемые разрешения
}

// Получение агрегированной битовой маски
$bitmask = $user->getPermissionBitmask();
```

### Использование PermissionChecker Service

```php
use DFiks\UnPerm\Facades\UnPerm;

$user = User::find(1);
$action = Action::where('slug', 'create-post')->first();

// Проверка, может ли модель выполнить действие
if (UnPerm::modelCan($user, 'create-post')) {
    // Разрешено
}

if (UnPerm::modelCan($user, $action)) {
    // Разрешено
}

// Проверка битов
if (UnPerm::modelHasBit($user, 0)) {
    // У пользователя есть бит 0
}

if (UnPerm::modelHasAllBits($user, $requiredMask)) {
    // У пользователя есть все требуемые биты
}
```

## Работа с битовыми масками

Битовые маски позволяют очень быстро проверять базовые разрешения без обращения к БД:

```php
// Установка битов в Action
$action->setBit(0)->save();
$action->setBit(1)->save();

// Проверка битов
if ($action->hasBit(0)) {
    // бит установлен
}

// Работа с масками
$action->setBits(0b1111); // устанавливает несколько битов
$action->unsetBits(0b0010); // снимает несколько битов
$action->toggleBit(2); // переключает бит

// Проверка нескольких битов
if ($action->hasAllBits(0b0011)) {
    // Биты 0 и 1 установлены
}

if ($action->hasAnyBits(0b0110)) {
    // Хотя бы один из битов 1 или 2 установлен
}
```

## Архитектура

### Таблицы

- `actions` - действия (create, edit, delete и т.д.)
- `roles` - роли (admin, editor, viewer и т.д.)
- `groups` - группы (content-managers, developers и т.д.)
- `roles_action` - связь ролей с действиями
- `groups_action` - связь групп с действиями
- `groups_roles` - связь групп с ролями
- `model_actions` - полиморфная связь моделей с действиями
- `model_roles` - полиморфная связь моделей с ролями
- `model_groups` - полиморфная связь моделей с группами

### Иерархия проверки

1. **Быстрая проверка**: Сначала проверяется битовая маска (очень быстро)
2. **Прямые действия**: Проверяются действия, назначенные напрямую модели
3. **Роли**: Проверяются действия через роли модели
4. **Группы**: Проверяются действия через группы модели

## Лицензия

MIT

