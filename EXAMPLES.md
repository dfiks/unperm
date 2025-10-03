# Примеры использования UnPerm с PermBit

## Настройка actions в конфигурации

```php
// config/unperm.php
return [
    'actions' => [
        'users' => [
            'view' => 'View users',
            'create' => 'Create users',
            'edit' => 'Edit users',
            'delete' => 'Delete users',
        ],
        'posts' => [
            'view' => 'View posts',
            'create' => 'Create posts',
            'edit' => 'Edit posts',
            'delete' => 'Delete posts',
            'publish' => 'Publish posts',
        ],
        'comments' => [
            'view' => 'View comments',
            'moderate' => 'Moderate comments',
        ],
    ],
];
```

## Синхронизация actions из конфига в БД

```bash
# Синхронизировать actions (обновит существующие и создаст новые)
php artisan unperm:sync-actions

# Полная очистка и пересоздание actions
php artisan unperm:sync-actions --fresh
```

## Работа с PermBit

### Получение битовых масок

```php
use DFiks\UnPerm\Support\PermBit;

// Получить bitmask для конкретного action
$bitmask = PermBit::getBitmask('users.create'); // '2'

// Получить позицию бита
$position = PermBit::getBitPosition('users.edit'); // 2

// Объединить несколько actions в одну маску
$combinedMask = PermBit::combine(['users.view', 'users.edit', 'posts.create']);
echo $combinedMask; // '13' (1 | 4 | 8)

// Пересобрать все маски из конфига
$allActions = PermBit::rebuild();
/*
[
    'users.view' => [
        'slug' => 'users.view',
        'name' => 'View users',
        'category' => 'users',
        'bit_position' => 0,
        'bitmask' => '1',
        'bitmask_hex' => '1',
    ],
    ...
]
*/
```

### Проверка битовых масок

```php
use DFiks\UnPerm\Support\PermBit;

$userMask = '15'; // binary: 1111 (имеет биты 0,1,2,3)

// Проверить конкретный action
if (PermBit::hasAction($userMask, 'users.view')) {
    echo 'Пользователь может просматривать users';
}

// Проверить все actions
if (PermBit::hasAllActions($userMask, ['users.view', 'users.create'])) {
    echo 'Пользователь может и просматривать, и создавать users';
}

// Проверить любой из actions
if (PermBit::hasAnyAction($userMask, ['users.edit', 'users.delete'])) {
    echo 'Пользователь может редактировать или удалять users';
}

// Проверить конкретный бит
if (PermBit::hasBit($userMask, 2)) {
    echo 'Бит 2 установлен';
}
```

### Манипуляция битовыми масками

```php
use DFiks\UnPerm\Support\PermBit;

$mask = '0';

// Добавить action
$mask = PermBit::addAction($mask, 'users.create');
echo $mask; // '2'

$mask = PermBit::addAction($mask, 'users.edit');
echo $mask; // '6' (2 | 4)

// Удалить action
$mask = PermBit::removeAction($mask, 'users.create');
echo $mask; // '4'

// Получить список всех actions в маске
$actions = PermBit::getActions('15');
print_r($actions); // ['users.view', 'users.create', 'users.edit', 'users.delete']
```

### Конвертация форматов

```php
use DFiks\UnPerm\Support\PermBit;

$mask = '255';

// В целое число
$int = PermBit::toInt($mask); // 255

// В строку
$str = PermBit::toString($mask); // '255'

// В hex
$hex = PermBit::toHex($mask); // 'ff'

// В binary
$bin = PermBit::toBinary($mask); // '11111111'
```

## Работа с моделями

### Использование HasPermissions trait

```php
use DFiks\UnPerm\Traits\HasPermissions;

class User extends Authenticatable
{
    use HasPermissions;
}

$user = User::find(1);

// Назначить actions
$user->assignAction('users.view');
$user->assignAction('posts.create');

// Проверить права используя PermBit
if ($user->hasPermissionAction('users.view')) {
    echo 'Может просматривать users';
}

// Проверить любой из actions
if ($user->hasAnyPermissionAction(['users.edit', 'posts.edit'])) {
    echo 'Может редактировать users или posts';
}

// Проверить все actions
if ($user->hasAllPermissionActions(['users.view', 'users.create'])) {
    echo 'Может и просматривать, и создавать users';
}

// Получить агрегированную маску (из всех actions, roles, groups)
$mask = $user->getPermissionBitmask(); // '7' (string)

// Конвертировать в удобный формат
use DFiks\UnPerm\Support\PermBit;

echo "Decimal: " . $mask;
echo "Hex: " . PermBit::toHex($mask);
echo "Binary: " . PermBit::toBinary($mask);

// Получить список всех actions
$actions = PermBit::getActions($mask);
print_r($actions); // ['users.view', 'users.create', 'users.edit']
```

### Работа с Role

```php
use DFiks\UnPerm\Models\{Action, Role};
use DFiks\UnPerm\Support\PermBit;

// Создать роль
$role = Role::create([
    'name' => 'Content Manager',
    'slug' => 'content-manager',
    'bitmask' => '0',
]);

// Назначить actions по slug
$postsView = Action::where('slug', 'posts.view')->first();
$postsCreate = Action::where('slug', 'posts.create')->first();
$postsEdit = Action::where('slug', 'posts.edit')->first();

$role->actions()->attach([$postsView->id, $postsCreate->id, $postsEdit->id]);

// Пересобрать bitmask на основе actions
$role->syncBitmaskFromActions()->save();

echo "Role bitmask: " . $role->bitmask;
echo "Role actions: " . implode(', ', PermBit::getActions($role->bitmask));

// Назначить роль пользователю
$user->assignRole($role);
```

### Работа с Group

```php
use DFiks\UnPerm\Models\{Group, Role};

$group = Group::create([
    'name' => 'Content Team',
    'slug' => 'content-team',
]);

// Добавить roles и actions
$group->roles()->attach($role->id);
$group->actions()->attach($commentsModerate->id);

// Пересобрать bitmask
$group->syncBitmaskFromRolesAndActions()->save();

// Назначить группу пользователю
$user->assignGroup($group);
```

## Команды для пересборки битовых масок

```bash
# Пересобрать маски для всех ролей и групп
php artisan unperm:rebuild-bitmask

# Только для ролей
php artisan unperm:rebuild-bitmask --roles

# Только для групп
php artisan unperm:rebuild-bitmask --groups
```

## Полный пример: Создание системы разрешений

```php
use DFiks\UnPerm\Models\{Action, Role, Group};
use DFiks\UnPerm\Support\PermBit;
use App\Models\User;

// 1. Синхронизировать actions из конфига
Artisan::call('unperm:sync-actions');

// 2. Создать роли
$editor = Role::create([
    'name' => 'Editor',
    'slug' => 'editor',
]);

$moderator = Role::create([
    'name' => 'Moderator',
    'slug' => 'moderator',
]);

// 3. Назначить actions ролям
$editorActions = Action::whereIn('slug', [
    'posts.view', 'posts.create', 'posts.edit'
])->get();

$editor->actions()->sync($editorActions->pluck('id'));
$editor->syncBitmaskFromActions()->save();

$moderatorActions = Action::whereIn('slug', [
    'comments.view', 'comments.moderate'
])->get();

$moderator->actions()->sync($moderatorActions->pluck('id'));
$moderator->syncBitmaskFromActions()->save();

// 4. Создать группу
$contentTeam = Group::create([
    'name' => 'Content Team',
    'slug' => 'content-team',
]);

$contentTeam->roles()->sync([$editor->id, $moderator->id]);
$contentTeam->syncBitmaskFromRolesAndActions()->save();

// 5. Назначить пользователю
$user = User::find(1);
$user->assignGroup($contentTeam);

// 6. Проверить права
if ($user->hasPermissionAction('posts.edit')) {
    // Пользователь может редактировать посты
}

if ($user->hasPermissionAction('comments.moderate')) {
    // Пользователь может модерировать комментарии
}

// 7. Получить все actions пользователя
$userMask = $user->getPermissionBitmask();
$allActions = PermBit::getActions($userMask);

echo "User has " . count($allActions) . " actions:\n";
foreach ($allActions as $action) {
    echo "- $action\n";
}
```

## Производительность с GMP

Благодаря использованию GMP, система поддерживает неограниченное количество разрешений:

```php
// Можно иметь тысячи разрешений без потери производительности
$actions = [];
for ($i = 0; $i < 1000; $i++) {
    $actions["action_$i"] = ['permission' => "Permission $i"];
}

config(['unperm.actions' => $actions]);
Artisan::call('unperm:sync-actions');

// Битовые маски будут корректно работать даже с огромными числами
$mask = PermBit::combine(array_keys($actions));
echo "Mask length: " . strlen($mask) . " digits"; // Может быть 100+ символов
echo "Binary length: " . strlen(PermBit::toBinary($mask)) . " bits";
```

