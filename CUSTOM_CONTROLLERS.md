# Создание собственных контроллеров для управления разрешениями

## Обзор

UnPerm предоставляет готовые сервисы и примеры контроллеров, которые вы можете использовать для создания собственной админ-панели управления разрешениями.

## Архитектура

```
┌─────────────────┐
│  Controllers    │ → Ваши контроллеры (примеры в examples/)
└─────────────────┘
        │
        ▼
┌─────────────────┐
│    Services     │ → Бизнес-логика (готовые сервисы)
└─────────────────┘
        │
        ▼
┌─────────────────┐
│     Models      │ → Eloquent модели
└─────────────────┘
```

## Доступные сервисы

### 1. ActionService

Управление действиями (actions).

```php
use DFiks\UnPerm\Services\ActionService;

$actionService = app(ActionService::class);

// Получить все actions
$actions = $actionService->getAll();

// С пагинацией и поиском
$actions = $actionService->paginate(15, 'users');

// Найти по ID
$action = $actionService->find($id);

// Найти по slug
$action = $actionService->findBySlug('users.view');

// Создать
$action = $actionService->create([
    'slug' => 'users.view',
    'description' => 'Просмотр пользователей',
]);

// Обновить
$action = $actionService->update($action, [
    'description' => 'Новое описание',
]);

// Удалить
$actionService->delete($action);

// Синхронизировать из конфига
$actionService->sync(config('unperm.actions'));

// Получить статистику
$usersCount = $actionService->getUsersCount($action);
$rolesCount = $actionService->getRolesCount($action);
```

### 2. RoleService

Управление ролями.

```php
use DFiks\UnPerm\Services\RoleService;

$roleService = app(RoleService::class);

// Получить все роли
$roles = $roleService->getAll();

// С пагинацией
$roles = $roleService->paginate(15, 'admin');

// Найти
$role = $roleService->find($id);
$role = $roleService->findBySlug('admin');

// Создать
$role = $roleService->create([
    'slug' => 'admin',
    'name' => 'Администратор',
    'description' => 'Полный доступ',
    'action_ids' => [$actionId1, $actionId2],
]);

// Обновить
$role = $roleService->update($role, [
    'name' => 'Супер администратор',
]);

// Управление actions
$roleService->attachAction($role, $action);
$roleService->detachAction($role, $action);
$roleService->syncActions($role, [$actionId1, $actionId2]);

// Управление resource actions
$roleService->attachResourceAction($role, $resourceAction);
$roleService->detachResourceAction($role, $resourceAction);

// Статистика
$usersCount = $roleService->getUsersCount($role);

// Синхронизация
$roleService->sync(config('unperm.roles'));
```

### 3. GroupService

Управление группами.

```php
use DFiks\UnPerm\Services\GroupService;

$groupService = app(GroupService::class);

// Получить все группы
$groups = $groupService->getAll();

// Создать
$group = $groupService->create([
    'slug' => 'managers',
    'name' => 'Менеджеры',
    'action_ids' => [$actionId1],
    'role_ids' => [$roleId1],
]);

// Управление actions и roles
$groupService->attachAction($group, $action);
$groupService->attachRole($group, $role);
$groupService->syncActions($group, [$actionId1, $actionId2]);
$groupService->syncRoles($group, [$roleId1]);

// Статистика
$usersCount = $groupService->getUsersCount($group);
```

### 4. UserPermissionService

Управление разрешениями пользователей.

```php
use DFiks\UnPerm\Services\UserPermissionService;

$service = app(UserPermissionService::class);

// Получить пользователей
$users = $service->getUsers(User::class, 15, 'john');

// Получить конкретного пользователя
$user = $service->getUser(User::class, $id);

// Назначить action
$service->assignAction($user, $action);
$service->removeAction($user, $action);
$service->syncActions($user, [$actionId1, $actionId2]);

// Назначить роль
$service->assignRole($user, $role);
$service->removeRole($user, $role);
$service->syncRoles($user, [$roleId1]);

// Назначить группу
$service->assignGroup($user, $group);
$service->removeGroup($user, $group);
$service->syncGroups($user, [$groupId1]);

// Массовые операции
$count = $service->bulkAssignRole([$userId1, $userId2], User::class, $role);
$count = $service->bulkAssignGroup([$userId1, $userId2], User::class, $group);

// Получить все разрешения
$permissions = $service->getAllPermissions($user);
```

## Примеры контроллеров

### 1. Базовый CRUD контроллер для Actions

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use DFiks\UnPerm\Http\Concerns\AuthorizesResources;
use DFiks\UnPerm\Services\ActionService;
use Illuminate\Http\Request;

class ActionController extends Controller
{
    use AuthorizesResources;

    public function __construct(
        protected ActionService $actionService
    ) {}

    public function index(Request $request)
    {
        // Проверка прав
        $this->authorizeAction('admin.permissions.view');

        // Получение данных с поиском
        $search = $request->input('search');
        $actions = $this->actionService->paginate(15, $search);

        return view('admin.actions.index', compact('actions', 'search'));
    }

    public function store(Request $request)
    {
        $this->authorizeAction('admin.permissions.manage');

        $validated = $request->validate([
            'slug' => 'required|string|unique:actions,slug',
            'description' => 'nullable|string',
        ]);

        $action = $this->actionService->create($validated);

        return response()->json([
            'message' => 'Action создан',
            'data' => $action,
        ], 201);
    }

    public function destroy(string $id)
    {
        $this->authorizeAction('admin.permissions.manage');

        $action = $this->actionService->find($id);
        
        if (!$action) {
            return response()->json(['message' => 'Не найден'], 404);
        }

        $this->actionService->delete($action);

        return response()->json(['message' => 'Удалено']);
    }
}
```

### 2. Управление разрешениями пользователя

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use DFiks\UnPerm\Http\Concerns\AuthorizesResources;
use DFiks\UnPerm\Services\UserPermissionService;
use DFiks\UnPerm\Services\RoleService;
use Illuminate\Http\Request;

class UserPermissionController extends Controller
{
    use AuthorizesResources;

    public function __construct(
        protected UserPermissionService $userService,
        protected RoleService $roleService
    ) {}

    public function show(string $id)
    {
        $this->authorizeAction('admin.users.view');

        $user = $this->userService->getUser(User::class, $id);
        $permissions = $this->userService->getAllPermissions($user);

        return view('admin.users.permissions', compact('user', 'permissions'));
    }

    public function assignRole(Request $request, string $id)
    {
        $this->authorizeAction('admin.users.manage');

        $user = $this->userService->getUser(User::class, $id);
        $role = $this->roleService->find($request->role_id);

        $this->userService->assignRole($user, $role);

        return back()->with('success', 'Роль назначена');
    }
}
```

## Готовые контроллеры

В папке `examples/Controllers/Permission/` находятся полностью готовые контроллеры:

- `ActionManagementController.php` - управление actions
- `RoleManagementController.php` - управление roles
- `GroupManagementController.php` - управление groups
- `UserPermissionManagementController.php` - управление разрешениями пользователей

**Как использовать:**

1. Скопируйте нужный контроллер в свой проект:
```bash
cp vendor/dfiks/unperm/examples/Controllers/Permission/ActionManagementController.php \
   app/Http/Controllers/Admin/Permission/
```

2. Настройте namespace и пути к views

3. Создайте маршруты

4. Адаптируйте под свои нужды

## Маршруты

### Пример маршрутов для админ-панели

```php
<?php

// routes/web.php

use App\Http\Controllers\Admin\Permission\ActionManagementController;
use App\Http\Controllers\Admin\Permission\RoleManagementController;
use App\Http\Controllers\Admin\Permission\GroupManagementController;
use App\Http\Controllers\Admin\Permission\UserPermissionManagementController;

Route::prefix('admin/permissions')->middleware(['auth', 'unperm:admin.permissions.view'])->group(function () {
    
    // Actions
    Route::prefix('actions')->name('admin.permissions.actions.')->group(function () {
        Route::get('/', [ActionManagementController::class, 'index'])->name('index');
        Route::get('/create', [ActionManagementController::class, 'create'])->name('create');
        Route::post('/', [ActionManagementController::class, 'store'])->name('store');
        Route::get('/{id}', [ActionManagementController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [ActionManagementController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ActionManagementController::class, 'update'])->name('update');
        Route::delete('/{id}', [ActionManagementController::class, 'destroy'])->name('destroy');
        Route::post('/sync', [ActionManagementController::class, 'sync'])->name('sync');
    });

    // Roles
    Route::prefix('roles')->name('admin.permissions.roles.')->group(function () {
        Route::get('/', [RoleManagementController::class, 'index'])->name('index');
        Route::post('/', [RoleManagementController::class, 'store'])->name('store');
        Route::get('/{id}', [RoleManagementController::class, 'show'])->name('show');
        Route::put('/{id}', [RoleManagementController::class, 'update'])->name('update');
        Route::delete('/{id}', [RoleManagementController::class, 'destroy'])->name('destroy');
        
        // Управление actions роли
        Route::post('/{id}/actions', [RoleManagementController::class, 'attachAction'])->name('attach-action');
        Route::delete('/{id}/actions/{actionId}', [RoleManagementController::class, 'detachAction'])->name('detach-action');
        Route::put('/{id}/actions', [RoleManagementController::class, 'syncActions'])->name('sync-actions');
    });

    // Groups
    Route::prefix('groups')->name('admin.permissions.groups.')->group(function () {
        Route::get('/', [GroupManagementController::class, 'index'])->name('index');
        Route::post('/', [GroupManagementController::class, 'store'])->name('store');
        Route::get('/{id}', [GroupManagementController::class, 'show'])->name('show');
        Route::put('/{id}', [GroupManagementController::class, 'update'])->name('update');
        Route::delete('/{id}', [GroupManagementController::class, 'destroy'])->name('destroy');
        
        Route::post('/{id}/actions', [GroupManagementController::class, 'attachAction'])->name('attach-action');
        Route::post('/{id}/roles', [GroupManagementController::class, 'attachRole'])->name('attach-role');
    });

    // Users
    Route::prefix('users')->name('admin.permissions.users.')->group(function () {
        Route::get('/', [UserPermissionManagementController::class, 'index'])->name('index');
        Route::get('/{id}', [UserPermissionManagementController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [UserPermissionManagementController::class, 'edit'])->name('edit');
        
        // Управление разрешениями
        Route::post('/{id}/actions', [UserPermissionManagementController::class, 'assignAction'])->name('assign-action');
        Route::delete('/{id}/actions/{actionId}', [UserPermissionManagementController::class, 'removeAction'])->name('remove-action');
        Route::post('/{id}/roles', [UserPermissionManagementController::class, 'assignRole'])->name('assign-role');
        Route::delete('/{id}/roles/{roleId}', [UserPermissionManagementController::class, 'removeRole'])->name('remove-role');
        Route::post('/{id}/groups', [UserPermissionManagementController::class, 'assignGroup'])->name('assign-group');
        Route::delete('/{id}/groups/{groupId}', [UserPermissionManagementController::class, 'removeGroup'])->name('remove-group');
        
        // Синхронизация
        Route::put('/{id}/permissions', [UserPermissionManagementController::class, 'syncPermissions'])->name('sync-permissions');
        
        // Массовые операции
        Route::post('/bulk/assign-role', [UserPermissionManagementController::class, 'bulkAssignRole'])->name('bulk-assign-role');
        Route::post('/bulk/assign-group', [UserPermissionManagementController::class, 'bulkAssignGroup'])->name('bulk-assign-group');
    });
});
```

## Использование трейта AuthorizesResources

Трейт `AuthorizesResources` предоставляет удобные методы для проверки прав в контроллерах:

```php
use DFiks\UnPerm\Http\Concerns\AuthorizesResources;

class MyController extends Controller
{
    use AuthorizesResources;

    public function index()
    {
        // Проверка глобального разрешения (с exception)
        $this->authorizeAction('users.view');
        
        // Безопасная проверка (без exception)
        if ($this->canAction('users.view')) {
            // ...
        }
    }

    public function show(Folder $folder)
    {
        // Проверка ресурса (с exception)
        $this->authorizeResource($folder, 'view');
        
        // Безопасная проверка
        if ($this->canResource($folder, 'view')) {
            // ...
        }
    }

    public function admin()
    {
        // Проверка роли
        $this->authorizeRole('admin');
        
        // Проверка группы
        $this->authorizeGroup('managers');
        
        // Проверка любого из прав
        $this->authorizeAnyAction(['users.view', 'users.manage']);
    }

    public function dangerous()
    {
        // Проверка супер-админа
        if (!$this->isSuperAdmin()) {
            return $this->forbiddenResponse('Only super admin');
        }
    }

    public function folders()
    {
        // Получить доступные ресурсы
        $folders = $this->getViewableResources(Folder::class)->get();
    }
}
```

## Создание собственного UI

### Пример Blade шаблона для списка actions

```blade
{{-- resources/views/admin/permissions/actions/index.blade.php --}}

@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Actions</h1>
        
        @can('admin.permissions.manage')
            <button onclick="createAction()" class="btn btn-primary">
                Создать Action
            </button>
        @endcan
    </div>

    {{-- Поиск --}}
    <form method="GET" class="mb-4">
        <input type="text" 
               name="search" 
               value="{{ $search }}" 
               placeholder="Поиск..."
               class="form-control">
    </form>

    {{-- Список --}}
    <table class="table">
        <thead>
            <tr>
                <th>Slug</th>
                <th>Описание</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @foreach($actions as $action)
            <tr>
                <td>{{ $action->slug }}</td>
                <td>{{ $action->description }}</td>
                <td>
                    <a href="{{ route('admin.permissions.actions.show', $action) }}">
                        Подробнее
                    </a>
                    
                    @can('admin.permissions.manage')
                        <button onclick="editAction('{{ $action->id }}')">
                            Редактировать
                        </button>
                        <button onclick="deleteAction('{{ $action->id }}')">
                            Удалить
                        </button>
                    @endcan
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $actions->links() }}
</div>

<script>
function createAction() {
    // Ваша логика создания
}

function editAction(id) {
    // Ваша логика редактирования
}

function deleteAction(id) {
    if (!confirm('Удалить action?')) return;
    
    fetch(`/admin/permissions/actions/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        }
    }).then(() => location.reload());
}
</script>
@endsection
```

## Интеграция с Vue.js / React

Для SPA используйте API endpoints:

```javascript
// Vue.js пример
import axios from 'axios';

export default {
    data() {
        return {
            actions: [],
        };
    },
    
    async mounted() {
        await this.loadActions();
    },
    
    methods: {
        async loadActions() {
            const response = await axios.get('/api/unperm/actions');
            this.actions = response.data.data;
        },
        
        async createAction(data) {
            await axios.post('/api/unperm/actions', data);
            await this.loadActions();
        },
        
        async deleteAction(id) {
            if (!confirm('Удалить?')) return;
            await axios.delete(`/api/unperm/actions/${id}`);
            await this.loadActions();
        },
    },
};
```

## Лучшие практики

1. **Всегда проверяйте права** перед выполнением операций
2. **Используйте сервисы** вместо прямой работы с моделями
3. **Валидируйте входные данные**
4. **Логируйте важные изменения**
5. **Используйте транзакции** для связанных операций

```php
use Illuminate\Support\Facades\DB;

public function updateRole(Request $request, string $id)
{
    DB::transaction(function () use ($request, $id) {
        $role = $this->roleService->find($id);
        $role = $this->roleService->update($role, $request->validated());
        $this->roleService->syncActions($role, $request->action_ids);
        
        \Log::info('Role updated', [
            'user' => auth()->id(),
            'role' => $role->id,
        ]);
    });
}
```

## Дополнительно

- [API документация](./API.md) - для создания API endpoints
- [Архитектура](./ARCHITECTURE.md) - детали внутреннего устройства
- [Интеграция](./INTEGRATION_GUIDE.md) - общее руководство по интеграции

