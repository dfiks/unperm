# Отладка Resource Permissions

## Проблема
После назначения прав на конкретный ресурс (папку), при запросе к API возвращается пустой массив.

## Пошаговая отладка

### 1. Проверьте что ResourceAction создан

Откройте psql/mysql и проверьте:

```sql
-- Проверяем созданные ResourceActions
SELECT id, slug, action_type, resource_type, resource_id, created_at 
FROM resource_actions 
ORDER BY created_at DESC 
LIMIT 5;

-- Для конкретной папки (замените на свой UUID)
SELECT * FROM resource_actions 
WHERE resource_id = 'ВАШ_UUID_ПАПКИ';
```

**Ожидаемый результат:** Должна быть запись с `slug` вида `folders.view.{uuid}`

### 2. Проверьте связь с пользователем

```sql
-- Проверяем связи пользователя с ResourceActions
SELECT mra.*, ra.slug, ra.action_type
FROM model_resource_actions mra
JOIN resource_actions ra ON ra.id = mra.resource_action_id
WHERE mra.model_type = 'App\\Domain\\Employees\\Models\\Employee'
  AND mra.model_id = 'ВАШ_UUID_ПОЛЬЗОВАТЕЛЯ';
```

**Ожидаемый результат:** Должны быть записи для всех назначенных прав

### 3. Проверьте загрузку в модели

Добавьте временно в ваш контроллер/запрос:

```php
$user = auth()->user();

// Проверяем что resourceActions загружены
$user->load('resourceActions');

\Log::debug('User Resource Actions:', [
    'user_id' => $user->id,
    'resource_actions_count' => $user->resourceActions->count(),
    'resource_actions' => $user->resourceActions->map(fn($ra) => [
        'id' => $ra->id,
        'slug' => $ra->slug,
        'action_type' => $ra->action_type,
    ])->toArray(),
]);

// Получаем папки с фильтрацией
$folders = \App\Models\Folder::viewableBy($user)->get();

\Log::debug('Viewable Folders:', [
    'count' => $folders->count(),
    'ids' => $folders->pluck('id')->toArray(),
]);
```

### 4. Проверьте метод viewableBy

В трейте `HasResourcePermissions` метод `scopeViewableBy` вызывает `scopeWhereUserCan`, который проверяет:
1. Загруженные `resourceActions` 
2. Или делает запрос к БД

Убедитесь что модель пользователя использует трейт `HasPermissions`:

```php
class Employee extends Model
{
    use \DFiks\UnPerm\Traits\HasPermissions;
    // ...
}
```

### 5. Проверьте что модель Folder использует правильный resourcePermissionKey

```php
class Folder extends Model
{
    use \DFiks\UnPerm\Traits\HasResourcePermissions;
    
    // ВАЖНО: Убедитесь что это соответствует тому что в БД
    protected $resourcePermissionKey = 'folders';
    
    // Или если не указано, используется table name:
    // protected $table = 'folders';
}
```

## Временный отладочный код

Добавьте в `src/Traits/HasResourcePermissions.php` временно в метод `userHasActionBySlug`:

```php
protected function userHasActionBySlug(Model $user, string $slug): bool
{
    \Log::debug('Checking userHasActionBySlug', [
        'user_id' => $user->id,
        'slug' => $slug,
        'actions_loaded' => $user->relationLoaded('actions'),
        'resourceActions_loaded' => $user->relationLoaded('resourceActions'),
    ]);
    
    // ... существующий код ...
    
    if (method_exists($user, 'resourceActions')) {
        $exists = $user->resourceActions()->where('slug', $slug)->exists();
        \Log::debug('Checked resourceActions', [
            'slug' => $slug,
            'exists' => $exists,
        ]);
        if ($exists) {
            return true;
        }
    }
    
    return false;
}
```

## Типичные проблемы

### Проблема 1: resourceActions не загружены
**Решение:** Загрузите связь перед использованием:
```php
$user->load('resourceActions');
$folders = Folder::viewableBy($user)->get();
```

### Проблема 2: Неправильный resourcePermissionKey
**Решение:** Проверьте что в `resource_actions.slug` используется тот же ключ:
- Если в модели `$resourcePermissionKey = 'folders'`
- То slug должен быть `folders.view.{uuid}`

### Проблема 3: Связь resourceActions() не определена
**Решение:** Убедитесь что модель пользователя использует `HasPermissions` трейт

### Проблема 4: PostgreSQL case-sensitivity
**Решение:** Проверьте что типы моделей в `model_type` совпадают с реальными namespace:
```sql
SELECT DISTINCT model_type FROM model_resource_actions;
```

## Быстрый тест

Выполните в tinker:

```php
$user = \App\Domain\Employees\Models\Employee::find('UUID');
$folder = \App\Models\Folder::find('UUID');

// Проверяем прямую проверку прав
$canView = $folder->userCan($user, 'view');
dd([
    'user_id' => $user->id,
    'folder_id' => $folder->id,
    'can_view' => $canView,
    'resource_actions_count' => $user->resourceActions()->count(),
    'expected_slug' => $folder->getResourcePermissionSlug('view'),
]);
```

