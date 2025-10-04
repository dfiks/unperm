# Примеры использования UnPerm

Эта папка содержит готовые примеры контроллеров, моделей и маршрутов для быстрого старта с UnPerm.

## Структура

```
examples/
├── Controllers/           # Примеры контроллеров
│   ├── Permission/       # Контроллеры для управления разрешениями
│   │   ├── ActionManagementController.php
│   │   ├── RoleManagementController.php
│   │   ├── GroupManagementController.php
│   │   └── UserPermissionManagementController.php
│   ├── FolderController.php    # Пример CRUD с ресурсными правами
│   ├── DocumentController.php  # Пример работы с документами
│   └── AdminController.php     # Пример админ-контроллера
├── Models/               # Примеры моделей
│   ├── Folder.php       # Модель с HasResourcePermissions
│   ├── Document.php     # Модель с автоматическими правами
│   └── User.php         # Модель пользователя с HasPermissions
├── routes.php           # Примеры маршрутов для приложения
└── permission-routes.php # Маршруты для админ-панели разрешений
```

## Быстрый старт

### 1. Контроллеры для управления разрешениями

Скопируйте нужные контроллеры в ваш проект:

```bash
# Создайте директорию
mkdir -p app/Http/Controllers/Admin/Permission

# Скопируйте контроллеры
cp vendor/dfiks/unperm/examples/Controllers/Permission/*.php \
   app/Http/Controllers/Admin/Permission/
```

Отредактируйте namespace в скопированных файлах на свой.

### 2. Добавьте маршруты

Скопируйте маршруты из `permission-routes.php` в ваш `routes/web.php`:

```php
// routes/web.php

use App\Http\Controllers\Admin\Permission\ActionManagementController;
// ... другие импорты

// Вставьте маршруты из examples/permission-routes.php
Route::prefix('admin/permissions')->middleware(['auth'])->group(function () {
    // ...
});
```

### 3. Создайте views (опционально)

Если используете Blade, создайте базовые шаблоны:

```
resources/views/admin/permissions/
├── actions/
│   ├── index.blade.php
│   ├── show.blade.php
│   └── edit.blade.php
├── roles/
│   └── ...
├── groups/
│   └── ...
└── users/
    └── ...
```

Или используйте встроенный Livewire UI:

```bash
php artisan vendor:publish --tag=unperm-views
```

### 4. Настройте разрешения

Добавьте необходимые actions в `config/unperm.php`:

```php
'actions' => [
    'admin.permissions.view' => 'Просмотр настроек разрешений',
    'admin.permissions.manage' => 'Управление разрешениями',
    'admin.users.view' => 'Просмотр пользователей',
    'admin.users.manage' => 'Управление пользователями',
],
```

Синхронизируйте:

```bash
php artisan unperm:sync
```

## Примеры использования

### Пример 1: Базовый CRUD для Actions

```php
// app/Http/Controllers/Admin/ActionController.php

use DFiks\UnPerm\Services\ActionService;

class ActionController extends Controller
{
    public function __construct(
        protected ActionService $actionService
    ) {}

    public function index()
    {
        $actions = $this->actionService->paginate(15);
        return view('admin.actions.index', compact('actions'));
    }

    public function store(Request $request)
    {
        $action = $this->actionService->create($request->validated());
        return redirect()->route('admin.actions.index');
    }
}
```

### Пример 2: Управление разрешениями пользователя

```php
use DFiks\UnPerm\Services\UserPermissionService;
use DFiks\UnPerm\Services\RoleService;

class UserController extends Controller
{
    public function assignRole(
        UserPermissionService $userService,
        RoleService $roleService,
        Request $request,
        string $userId
    ) {
        $user = $userService->getUser(User::class, $userId);
        $role = $roleService->find($request->role_id);
        
        $userService->assignRole($user, $role);
        
        return back()->with('success', 'Роль назначена');
    }
}
```

### Пример 3: Работа с ресурсными разрешениями

```php
use DFiks\UnPerm\Http\Concerns\AuthorizesResources;

class FolderController extends Controller
{
    use AuthorizesResources;

    public function show(Folder $folder)
    {
        // Автоматическая проверка прав
        $this->authorizeResource($folder, 'view');
        
        return view('folders.show', compact('folder'));
    }

    public function share(Request $request, Folder $folder)
    {
        $this->authorizeResource($folder, 'share');
        
        $user = User::find($request->user_id);
        
        // Предоставить доступ
        $this->grantResourceAccess($user, $folder, 'view');
        $this->grantResourceAccess($user, $folder, 'update');
        
        return back()->with('success', 'Доступ предоставлен');
    }
}
```

## Доступные сервисы

### ActionService
```php
$actionService = app(\DFiks\UnPerm\Services\ActionService::class);
$actionService->create(['slug' => 'users.view']);
$actionService->update($action, ['description' => 'New']);
$actionService->delete($action);
```

### RoleService
```php
$roleService = app(\DFiks\UnPerm\Services\RoleService::class);
$roleService->create(['slug' => 'admin', 'name' => 'Administrator']);
$roleService->attachAction($role, $action);
$roleService->syncActions($role, [$actionId1, $actionId2]);
```

### GroupService
```php
$groupService = app(\DFiks\UnPerm\Services\GroupService::class);
$groupService->create(['slug' => 'managers', 'name' => 'Managers']);
$groupService->attachRole($group, $role);
```

### UserPermissionService
```php
$userService = app(\DFiks\UnPerm\Services\UserPermissionService::class);
$userService->assignRole($user, $role);
$userService->assignGroup($user, $group);
$userService->bulkAssignRole([$userId1, $userId2], User::class, $role);
```

## Helper функции

Все helper функции доступны глобально:

```php
// Проверка разрешений
currentUserCan('users.view');
currentUserHasRole('admin');
isSuperadmin();

// Ресурсные разрешения
userCanResource($folder, 'view');
grantResourcePermission($user, $folder, 'view');
revokeResourcePermission($user, $folder, 'view');
usersWithResourceAccess($folder, 'view');

// Получение доступных ресурсов
$folders = viewableResources(Folder::class)->get();
```

## Трейт AuthorizesResources

Используйте в контроллерах для удобной авторизации:

```php
use DFiks\UnPerm\Http\Concerns\AuthorizesResources;

class MyController extends Controller
{
    use AuthorizesResources;

    public function index()
    {
        $this->authorizeAction('users.view');
        
        $users = $this->getViewableResources(User::class)->get();
    }

    public function show(Folder $folder)
    {
        $this->authorizeResource($folder, 'view');
    }

    public function admin()
    {
        if (!$this->isSuperAdmin()) {
            return $this->forbiddenResponse();
        }
    }
}
```

## API Routes

Для API используйте встроенные endpoints:

```
GET    /api/unperm/actions
POST   /api/unperm/actions
PUT    /api/unperm/actions/{id}
DELETE /api/unperm/actions/{id}

GET    /api/unperm/roles
POST   /api/unperm/roles/{id}/actions
DELETE /api/unperm/roles/{id}/actions/{actionId}

// И т.д.
```

См. полную документацию в [API.md](../API.md)

## Дополнительные ресурсы

- [CUSTOM_CONTROLLERS.md](../CUSTOM_CONTROLLERS.md) - Полное руководство по созданию контроллеров
- [INTEGRATION_GUIDE.md](../INTEGRATION_GUIDE.md) - Руководство по интеграции
- [ARCHITECTURE.md](../ARCHITECTURE.md) - Архитектура системы
- [API.md](../API.md) - API документация

## Поддержка

По вопросам обращайтесь к команде DFiks.

