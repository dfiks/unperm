# Руководство по интеграции UnPerm в реальные проекты

## Содержание

1. [Установка](#установка)
2. [Базовая настройка](#базовая-настройка)
3. [Использование в контроллерах](#использование-в-контроллерах)
4. [Helper функции](#helper-функции)
5. [Middleware](#middleware)
6. [Blade директивы](#blade-директивы)
7. [Лучшие практики](#лучшие-практики)
8. [Примеры из реальных проектов](#примеры-из-реальных-проектов)
9. [Производительность](#производительность)
10. [Отладка](#отладка)

---

## Установка

### 1. Добавить пакет в composer.json

```json
{
    "require": {
        "dfiks/unperm": "^1.0"
    },
    "repositories": [
        {
            "type": "path",
            "url": "../packages/unperm"
        }
    ]
}
```

### 2. Установить пакет

```bash
composer require dfiks/unperm
```

### 3. Опубликовать конфигурацию и миграции

```bash
php artisan vendor:publish --tag=unperm-config
php artisan vendor:publish --tag=unperm-migrations
```

### 4. Запустить миграции

```bash
php artisan migrate
```

### 5. Опубликовать UI (опционально)

```bash
php artisan vendor:publish --tag=unperm-views
```

---

## Базовая настройка

### 1. Настроить модель пользователя

```php
// app/Models/User.php

namespace App\Models;

use DFiks\UnPerm\Traits\HasPermissions;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasPermissions;
    
    // ... остальной код модели
}
```

### 2. Настроить модели ресурсов

```php
// app/Models/Folder.php

namespace App\Models;

use DFiks\UnPerm\Traits\HasResourcePermissions;
use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    use HasResourcePermissions;
    
    protected string $resourcePermissionKey = 'folders';
    
    // ... остальной код модели
}
```

### 3. Настроить config/unperm.php

```php
return [
    // Кеширование
    'cache' => [
        'enabled' => env('UNPERM_CACHE_ENABLED', true),
        'ttl' => env('UNPERM_CACHE_TTL', 3600),
    ],
    
    // Супер-админы
    'superadmins' => [
        'enabled' => true,
        'models' => [
            \App\Models\Admin::class, // Все админы - суперадмины
        ],
        'emails' => [
            'admin@example.com',
        ],
    ],
    
    // Глобальные действия
    'actions' => [
        'users.view' => 'Просмотр пользователей',
        'users.create' => 'Создание пользователей',
        'users.update' => 'Редактирование пользователей',
        'users.delete' => 'Удаление пользователей',
        
        'folders.create' => 'Создание папок',
        'documents.create' => 'Создание документов',
    ],
    
    // Роли
    'roles' => [
        'admin' => [
            'name' => 'Администратор',
            'actions' => [
                'users.view',
                'users.create',
                'users.update',
                'users.delete',
            ],
        ],
        'manager' => [
            'name' => 'Менеджер',
            'actions' => [
                'users.view',
                'folders.create',
                'documents.create',
            ],
        ],
    ],
];
```

### 4. Синхронизировать конфигурацию с БД

```bash
php artisan unperm:sync
```

---

## Использование в контроллерах

### Базовый контроллер с трейтом

```php
namespace App\Http\Controllers;

use DFiks\UnPerm\Http\Concerns\AuthorizesResources;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesResources;
}
```

### Пример CRUD контроллера

```php
namespace App\Http\Controllers;

use App\Models\Folder;
use Illuminate\Http\Request;

class FolderController extends Controller
{
    /**
     * Список папок доступных пользователю.
     */
    public function index()
    {
        // Автоматическая фильтрация по правам
        $folders = $this->getViewableResources(Folder::class)
            ->paginate(20);
        
        return view('folders.index', compact('folders'));
    }
    
    /**
     * Показать папку.
     */
    public function show(Folder $folder)
    {
        // Проверка прав с автоматическим 403
        $this->authorizeResource($folder, 'view');
        
        return view('folders.show', compact('folder'));
    }
    
    /**
     * Создать папку.
     */
    public function store(Request $request)
    {
        // Проверка глобального права
        $this->authorizeAction('folders.create');
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        
        $folder = Folder::create([
            ...$validated,
            'creator_id' => auth()->id(),
        ]);
        
        // Дать создателю все права на папку
        grantResourcePermission(auth()->user(), $folder, 'view');
        grantResourcePermission(auth()->user(), $folder, 'update');
        grantResourcePermission(auth()->user(), $folder, 'delete');
        
        return redirect()
            ->route('folders.show', $folder)
            ->with('success', 'Папка создана');
    }
    
    /**
     * Обновить папку.
     */
    public function update(Request $request, Folder $folder)
    {
        $this->authorizeResource($folder, 'update');
        
        $folder->update($request->validated());
        
        return back()->with('success', 'Папка обновлена');
    }
    
    /**
     * Удалить папку.
     */
    public function destroy(Folder $folder)
    {
        $this->authorizeResource($folder, 'delete');
        
        $folder->delete();
        
        return redirect()
            ->route('folders.index')
            ->with('success', 'Папка удалена');
    }
    
    /**
     * Поделиться папкой.
     */
    public function share(Request $request, Folder $folder)
    {
        $this->authorizeResource($folder, 'share');
        
        $user = User::findOrFail($request->user_id);
        $permissions = $request->permissions; // ['view', 'update']
        
        foreach ($permissions as $permission) {
            $this->grantResourceAccess($user, $folder, $permission);
        }
        
        return back()->with('success', 'Доступ предоставлен');
    }
}
```

### API контроллер

```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\JsonResponse;

class DocumentApiController extends Controller
{
    public function index(): JsonResponse
    {
        $documents = $this->getViewableResources(Document::class)
            ->with(['folder', 'author'])
            ->paginate(20);
        
        return response()->json($documents);
    }
    
    public function show(Document $document): JsonResponse
    {
        // Безопасная проверка без исключения
        if (!$this->canResource($document, 'view')) {
            return $this->forbiddenResponse('No access to this document');
        }
        
        return response()->json(['data' => $document]);
    }
    
    public function update(Request $request, Document $document): JsonResponse
    {
        if (!$this->canResource($document, 'update')) {
            return $this->forbiddenResponse();
        }
        
        $document->update($request->validated());
        
        return response()->json([
            'message' => 'Document updated',
            'data' => $document,
        ]);
    }
}
```

---

## Helper функции

### Глобальные разрешения

```php
// Проверка разрешения
if (currentUserCan('users.view')) {
    // ...
}

// Проверка роли
if (currentUserHasRole('admin')) {
    // ...
}

// Проверка группы
if (currentUserHasGroup('managers')) {
    // ...
}

// Проверка супер-админа
if (isSuperadmin()) {
    // ...
}

// Авторизация с исключением
authorize_permission('users.delete');
```

### Ресурсные разрешения

```php
// Проверка доступа к ресурсу
if (userCanResource($folder, 'view')) {
    // ...
}

// Авторизация ресурса с исключением
authorizeResource($folder, 'update');

// Предоставить доступ
grantResourcePermission($user, $folder, 'view');

// Отозвать доступ
revokeResourcePermission($user, $folder, 'view');

// Получить пользователей с доступом
$users = usersWithResourceAccess($folder, 'view');

// Получить доступные ресурсы
$folders = viewableResources(Folder::class)->get();
```

---

## Middleware

### Глобальные разрешения

```php
// routes/web.php

// Проверка действия
Route::get('/users', [UserController::class, 'index'])
    ->middleware('unperm:users.view');

// Множественные проверки
Route::prefix('admin')->middleware(['auth', 'unperm:admin.access'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/users', [AdminController::class, 'users']);
});
```

### Ресурсные разрешения

```php
// Проверка доступа к ресурсу
Route::get('/folders/{folder}/edit', [FolderController::class, 'edit'])
    ->middleware('unperm:folders,update');

// Параметр берется из route model binding
Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])
    ->middleware('unperm:documents,delete');
```

### Кастомный middleware

```php
// app/Http/Middleware/CheckRole.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        if (!currentUserHasRole($role)) {
            abort(403, "You must have role: {$role}");
        }
        
        return $next($request);
    }
}

// Регистрация в app/Http/Kernel.php
protected $middlewareAliases = [
    'role' => \App\Http\Middleware\CheckRole::class,
];

// Использование
Route::get('/admin', [AdminController::class, 'index'])
    ->middleware('role:admin');
```

---

## Blade директивы

### Создание кастомных директив

```php
// app/Providers/AppServiceProvider.php

use Illuminate\Support\Facades\Blade;

public function boot()
{
    // Проверка разрешения
    Blade::if('can', function (string $action) {
        return currentUserCan($action);
    });
    
    // Проверка роли
    Blade::if('hasRole', function (string $role) {
        return currentUserHasRole($role);
    });
    
    // Проверка супер-админа
    Blade::if('superadmin', function () {
        return isSuperadmin();
    });
    
    // Проверка доступа к ресурсу
    Blade::if('canResource', function ($resource, string $action) {
        return userCanResource($resource, $action);
    });
}
```

### Использование в Blade

```blade
{{-- Проверка глобального разрешения --}}
@can('users.view')
    <a href="{{ route('users.index') }}">Пользователи</a>
@endcan

{{-- Проверка роли --}}
@hasRole('admin')
    <a href="{{ route('admin.dashboard') }}">Админ-панель</a>
@endhasRole

{{-- Проверка супер-админа --}}
@superadmin
    <button class="btn-danger">Опасная операция</button>
@endsuperadmin

{{-- Проверка доступа к ресурсу --}}
@canResource($folder, 'update')
    <a href="{{ route('folders.edit', $folder) }}">Редактировать</a>
@endcanResource

@canResource($folder, 'delete')
    <form method="POST" action="{{ route('folders.destroy', $folder) }}">
        @csrf
        @method('DELETE')
        <button type="submit">Удалить</button>
    </form>
@endcanResource

{{-- Комбинированные проверки --}}
@can('folders.create')
    <a href="{{ route('folders.create') }}" class="btn btn-primary">
        Создать папку
    </a>
@else
    <p class="text-muted">У вас нет прав на создание папок</p>
@endcan
```

---

## Лучшие практики

### 1. Структура разрешений

```php
// Используйте понятную структуру slug'ов
'resource.action' // users.view, folders.create

// Группируйте связанные действия
'users.view'
'users.create'
'users.update'
'users.delete'

// Используйте иерархию: Actions → Roles → Groups
```

### 2. Автоматическое предоставление прав создателю

```php
class Folder extends Model
{
    use HasResourcePermissions;
    
    protected static function booted()
    {
        static::created(function (Folder $folder) {
            if ($folder->creator) {
                grantResourcePermission($folder->creator, $folder, 'view');
                grantResourcePermission($folder->creator, $folder, 'update');
                grantResourcePermission($folder->creator, $folder, 'delete');
                grantResourcePermission($folder->creator, $folder, 'share');
            }
        });
    }
}
```

### 3. Централизованная политика

```php
// app/Policies/FolderPolicy.php

namespace App\Policies;

use App\Models\Folder;
use App\Models\User;

class FolderPolicy
{
    public function view(User $user, Folder $folder): bool
    {
        return $folder->userCan($user, 'view') 
            || $user->owns($folder);
    }
    
    public function update(User $user, Folder $folder): bool
    {
        return $folder->userCan($user, 'update') 
            || $user->owns($folder);
    }
    
    public function share(User $user, Folder $folder): bool
    {
        return $folder->userCan($user, 'share')
            || $user->owns($folder)
            || $user->isSuperAdmin();
    }
}
```

### 4. Использование scopes

```php
// В контроллере
$folders = Folder::viewableBy(auth()->user())
    ->where('archived', false)
    ->orderBy('created_at', 'desc')
    ->paginate(20);

// Или через helper
$folders = viewableResources(Folder::class)
    ->where('archived', false)
    ->get();
```

### 5. Массовые операции

```php
public function bulkShare(Request $request)
{
    $folderIds = $request->folder_ids;
    $userIds = $request->user_ids;
    $permissions = $request->permissions;
    
    $folders = Folder::whereIn('id', $folderIds)->get();
    $users = User::whereIn('id', $userIds)->get();
    
    foreach ($folders as $folder) {
        // Проверить права только один раз
        $this->authorizeResource($folder, 'share');
        
        foreach ($users as $user) {
            foreach ($permissions as $permission) {
                grantResourcePermission($user, $folder, $permission);
            }
        }
    }
    
    return back()->with('success', 'Доступ предоставлен');
}
```

---

## Примеры из реальных проектов

### Система управления документами

```php
// Наследование прав от папки
class Document extends Model
{
    use HasResourcePermissions;
    
    protected string $resourcePermissionKey = 'documents';
    
    public function inheritPermissionsFromFolder()
    {
        if (!$this->folder) {
            return;
        }
        
        // Получить всех пользователей с доступом к папке
        $users = usersWithResourceAccess($this->folder, 'view');
        
        foreach ($users as $user) {
            grantResourcePermission($user, $this, 'view');
        }
    }
}
```

### Многоуровневая система доступа

```php
class Project extends Model
{
    use HasResourcePermissions;
    
    public function canAccess(User $user, string $action): bool
    {
        // 1. Проверка супер-админа
        if ($user->isSuperAdmin()) {
            return true;
        }
        
        // 2. Проверка владельца
        if ($this->owner_id === $user->id) {
            return true;
        }
        
        // 3. Проверка прямых прав
        if ($this->userCan($user, $action)) {
            return true;
        }
        
        // 4. Проверка через команду проекта
        if ($this->team && $this->team->hasMember($user)) {
            return $this->team->memberCan($user, $action);
        }
        
        return false;
    }
}
```

### CRM система

```php
// Доступ к клиентам по регионам
class Client extends Model
{
    use HasResourcePermissions;
    
    public function scopeForManager(Builder $query, User $manager)
    {
        // Супер-админ видит всех
        if ($manager->isSuperAdmin()) {
            return $query;
        }
        
        // Менеджер видит своих клиентов
        if ($manager->hasRole('manager')) {
            return $query->where('manager_id', $manager->id);
        }
        
        // Остальные - только с явным доступом
        return $query->viewableBy($manager);
    }
}
```

---

## Производительность

### 1. Используйте кеширование

```php
// config/unperm.php
'cache' => [
    'enabled' => true,
    'ttl' => 3600,
    'cache_user_bitmasks' => true,
    'cache_role_bitmasks' => true,
],
```

### 2. Eager loading

```php
// Плохо: N+1 запросов
$users = User::all();
foreach ($users as $user) {
    $user->roles; // +1 запрос
    $user->actions; // +1 запрос
}

// Хорошо: 3 запроса
$users = User::with(['roles', 'actions', 'groups'])->get();
```

### 3. Используйте scopes вместо фильтрации в PHP

```php
// Плохо: загружаем все и фильтруем в PHP
$allFolders = Folder::all();
$viewable = $allFolders->filter(fn($f) => $f->userCan($user, 'view'));

// Хорошо: фильтруем на уровне БД
$viewable = Folder::viewableBy($user)->get();
```

### 4. Битмаски для массовых проверок

```php
// Используйте битмаски для быстрой проверки
// Вместо проверки каждого action отдельно
if ($user->hasAction('users.view') && $user->hasAction('users.create')) {
    // ...
}

// Используйте битмаски (происходит автоматически)
$bitmask = $user->getPermissionBitmask();
```

---

## Отладка

### 1. Диагностика разрешений

```bash
php artisan unperm:diagnose-resource-permissions {user_id} {resource_type} {resource_id}
```

### 2. Проверка в коде

```php
// Узнать почему пользователь супер-админ
$checker = new \DFiks\UnPerm\Support\SuperAdminChecker();
$reason = $checker->getReason($user);
dd($reason); // "Модель App\Models\Admin в списке суперадминов"

// Посмотреть все разрешения пользователя
dd([
    'actions' => $user->actions->pluck('slug'),
    'roles' => $user->roles->pluck('slug'),
    'groups' => $user->groups->pluck('slug'),
    'resource_actions' => $user->resourceActions->pluck('slug'),
    'bitmask' => $user->getPermissionBitmask(),
]);
```

### 3. Логирование

```php
// В контроллере
\Log::info('Permission check', [
    'user' => auth()->id(),
    'resource' => get_class($resource) . ':' . $resource->id,
    'action' => 'view',
    'result' => $resource->userCan(auth()->user(), 'view'),
]);
```

### 4. Мониторинг производительности

```php
// Измерить время проверки
$start = microtime(true);
$canView = $folder->userCan($user, 'view');
$duration = (microtime(true) - $start) * 1000;

\Log::debug('Permission check duration', [
    'duration_ms' => $duration,
    'resource' => 'Folder:' . $folder->id,
]);
```

---

## Дополнительные ресурсы

- [API документация](./API.md)
- [Архитектура](./ARCHITECTURE.md)
- [Примеры использования](./EXAMPLES.md)
- [ROW-LEVEL разрешения](./ROW_LEVEL_PERMISSIONS.md)

---

## Поддержка

По вопросам обращайтесь к команде DFiks.

