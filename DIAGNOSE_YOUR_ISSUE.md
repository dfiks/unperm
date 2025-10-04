# 🔧 Диагностика проблемы с Resource Permissions

## Ваша проблема
"Дал права на ресурс пользователю, но при запросе к API возвращается пустой массив"

## Шаг 1: Запустите команду диагностики

```bash
php artisan unperm:diagnose-resources
```

Это покажет общую картину. Затем для детальной проверки конкретного пользователя:

```bash
php artisan unperm:diagnose-resources "App\\Domain\\Employees\\Models\\Employee" "UUID_ПОЛЬЗОВАТЕЛЯ" "App\\Models\\Folder"
```

Замените:
- `App\\Domain\\Employees\\Models\\Employee` - на ваш класс модели пользователя
- `UUID_ПОЛЬЗОВАТЕЛЯ` - на реальный UUID пользователя  
- `App\\Models\\Folder` - на вашу модель ресурса (папки, проекты, и т.д.)

## Шаг 2: Проверьте настройку моделей

### Модель пользователя (Employee)

```php
namespace App\Domain\Employees\Models;

use DFiks\UnPerm\Traits\HasPermissions;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Employee extends Authenticatable
{
    use HasPermissions;  // ← ОБЯЗАТЕЛЬНО!
    
    // ... остальной код
}
```

### Модель ресурса (Folder)

```php
namespace App\Models;

use DFiks\UnPerm\Traits\HasResourcePermissions;
use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    use HasResourcePermissions;  // ← ОБЯЗАТЕЛЬНО!
    
    // ВАЖНО: Укажите явно, если table != 'folders'
    protected $resourcePermissionKey = 'folders';
    
    // ... остальной код
}
```

## Шаг 3: Проверьте ваш API endpoint

### Типичная ошибка ❌

```php
// НЕ РАБОТАЕТ - не фильтрует по правам
public function index(Request $request)
{
    $folders = Folder::all(); // Возвращает ВСЕ папки
    return response()->json($folders);
}
```

### Правильный подход ✅

```php
public function index(Request $request)
{
    $user = $request->user(); // или auth()->user()
    
    // ОБЯЗАТЕЛЬНО загрузите связь!
    $user->load('resourceActions');
    
    // Используйте scope для фильтрации
    $folders = Folder::viewableBy($user)->get();
    
    return response()->json($folders);
}
```

## Шаг 4: Отладка в реальном времени

Добавьте временно в ваш контроллер:

```php
public function index(Request $request)
{
    $user = $request->user();
    $user->load(['actions', 'resourceActions']);
    
    // ОТЛАДКА: Смотрим что у пользователя
    \Log::info('User permissions check', [
        'user_id' => $user->id,
        'user_email' => $user->email,
        'global_actions' => $user->actions->pluck('slug')->toArray(),
        'resource_actions_count' => $user->resourceActions->count(),
        'resource_actions_sample' => $user->resourceActions->take(5)->pluck('slug')->toArray(),
    ]);
    
    // Получаем папки
    $folders = Folder::viewableBy($user)->get();
    
    \Log::info('Folders result', [
        'total_folders_in_db' => Folder::count(),
        'viewable_folders' => $folders->count(),
        'folder_ids' => $folders->pluck('id')->toArray(),
    ]);
    
    return response()->json($folders);
}
```

Затем сделайте запрос к API и проверьте логи:

```bash
tail -f storage/logs/laravel.log
```

## Шаг 5: Проверьте прямо в БД

### PostgreSQL

```sql
-- 1. Проверяем что ResourceActions созданы
SELECT id, slug, action_type, resource_type, resource_id, created_at 
FROM resource_actions 
WHERE resource_type LIKE '%Folder%'
ORDER BY created_at DESC 
LIMIT 10;

-- 2. Проверяем связь с пользователем
SELECT 
    mra.*,
    ra.slug as resource_action_slug,
    ra.action_type
FROM model_resource_actions mra
JOIN resource_actions ra ON ra.id = mra.resource_action_id  
WHERE mra.model_type = 'App\Domain\Employees\Models\Employee'
  AND mra.model_id = 'ВАШ_UUID_ПОЛЬЗОВАТЕЛЯ';

-- 3. Проверяем формат slugs
SELECT DISTINCT slug 
FROM resource_actions 
WHERE resource_type LIKE '%Folder%' 
LIMIT 5;
-- Должно быть: folders.view.{uuid}, folders.edit.{uuid}, и т.д.
```

## Шаг 6: Типичные проблемы и решения

### Проблема 1: `viewableBy()` возвращает пустой массив

**Причина:** Связь `resourceActions` не загружена

**Решение:**
```php
$user->load('resourceActions');
$folders = Folder::viewableBy($user)->get();
```

### Проблема 2: Rights есть в БД, но `userCan()` возвращает `false`

**Причина:** Неправильный `resourcePermissionKey`

**Проверка:**
```php
$folder = Folder::first();
$expectedSlug = $folder->getResourcePermissionSlug('view');
// Должно быть: folders.view.{uuid}

// Проверьте что в БД такой же формат:
$actualSlug = ResourceAction::where('resource_id', $folder->id)
    ->where('action_type', 'view')
    ->value('slug');
    
if ($expectedSlug !== $actualSlug) {
    // ПРОБЛЕМА! Нужно исправить resourcePermissionKey
}
```

### Проблема 3: Права назначены, но не отображаются в UI Actions

**Причина:** Нет глобального Action в таблице `actions`

**Решение:** Откройте UI UnPerm -> Actions. Внизу страницы будет секция "Resource Actions без глобального Action". Нажмите кнопку "Создать Global Action".

Или создайте вручную:
```php
\DFiks\UnPerm\Models\Action::create([
    'name' => 'View Folders',
    'slug' => 'folders.view',
    'bitmask' => '0',
    'description' => 'View folders permission',
]);

\DFiks\UnPerm\Support\PermBit::rebuild();
```

### Проблема 4: PostgreSQL ошибка с model_type

**Причина:** Case-sensitivity или неправильный namespace

**Проверка:**
```sql
SELECT DISTINCT model_type FROM model_resource_actions;
```

Убедитесь что `model_type` точно совпадает с вашим классом (включая namespace и `\\` вместо `\`).

## Шаг 7: Финальная проверка

Выполните в tinker:

```php
$user = \App\Domain\Employees\Models\Employee::where('email', 'test2@unpass.ru')->first();
$folder = \App\Models\Folder::first();

// Тест 1: Прямая проверка прав
$canView = $folder->userCan($user, 'view');
dump('Can view: ' . ($canView ? 'YES' : 'NO'));

// Тест 2: Scope
$user->load('resourceActions');
$viewable = \App\Models\Folder::viewableBy($user)->get();
dump('Viewable folders: ' . $viewable->count());

// Тест 3: Проверка связи
$user->load('resourceActions');
dump('User has resource actions: ' . $user->resourceActions->count());
dump('Slugs: ', $user->resourceActions->pluck('slug')->toArray());
```

## Нужна помощь?

Если после всех шагов не работает, отправьте вывод команды:

```bash
php artisan unperm:diagnose-resources "ВАШ_USER_MODEL" "UUID" "ВАШ_RESOURCE_MODEL" > diagnosis.txt
```

И содержимое:
```sql
SELECT * FROM resource_actions LIMIT 5;
SELECT * FROM model_resource_actions LIMIT 5;
```

