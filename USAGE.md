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

Генерируйте файл для автодополнения строк в параметрах:

```bash
php artisan unperm:generate-ide-helper --meta
```

Это создаст два файла:

1. **`_ide_helper_permissions.php`** - с методами и константами
2. **`.phpstorm.meta.php`** - для автодополнения строк в PhpStorm/IDE

После генерации **перезапустите PhpStorm** и вы получите автодополнение:

```php
// Автодополнение в строковых параметрах!
$user->hasAction('');      // IDE покажет: users.view, posts.create, ...
$user->assignRole('');     // IDE покажет: admin, editor, ...
$user->hasGroup('');       // IDE покажет: content-team, ...

// Или используйте константы:
$user->hasAction(UnPermActions::USERS_VIEW);
$user->hasRole(UnPermRoles::ADMIN);
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
php artisan unperm:generate-ide-helper           # Генерация _ide_helper_permissions.php
php artisan unperm:generate-ide-helper --meta    # + .phpstorm.meta.php для автодополнения строк
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

### Лимиты и Оптимизация

Пакет использует тип `TEXT` для хранения битовых масок, что позволяет:

- **До 65,535 символов** в поле `bitmask`
- **До ~20,000 permissions** практически (2^20000 ≈ 6000 цифр)
- **До ~200,000 permissions** теоретически (лимит TEXT поля)

#### Оптимизация для разреженных данных

Если у пользователей **мало назначенных permissions** из большого набора (например, 10 из 10,000), используйте `BitmaskOptimizer`:

```php
use DFiks\UnPerm\Support\BitmaskOptimizer;

// Вместо хранения 2^9999 (3000+ цифр)
// Храним только индексы: [5, 42, 1000] (~20 байт)

// Получить статистику
$stats = BitmaskOptimizer::getStats($user->getPermissionBitmask());
// ['bits_set' => 10, 'total_size' => 3015, 'compressed_size' => 35, 'compression_ratio' => 98.8]

// Автоматическая оптимизация
$optimized = BitmaskOptimizer::optimize($bitmask);
// ['type' => 'indices', 'data' => '[5,42,1000]']

// Восстановление
$bitmask = BitmaskOptimizer::restore($optimized);
```

**Анализ эффективности:**

```bash
php artisan unperm:analyze-bitmask
```

Эта команда покажет:
- Текущий размер всех битовых масок
- Потенциальную экономию при оптимизации
- Рекомендации по использованию

**Когда использовать оптимизацию:**
- ✅ Мало permissions на пользователя (< 5% от общего числа)
- ✅ Большое количество permissions в системе (> 1000)
- ❌ Плотное назначение permissions (> 50% от общего числа)

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
