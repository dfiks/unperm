# 🔐 Permission Gate - Использование

## Декларативные правила в контроллерах

### Базовый пример

```php
use DFiks\UnPerm\Traits\AuthorizesPermissions;

class UserController extends Controller
{
    use AuthorizesPermissions;

    protected function permissionRules(): array
    {
        return [
            'index'   => 'users.view',           // Один action
            'store'   => 'users.create',
            'update'  => 'users.edit',
            'destroy' => 'users.delete',
        ];
    }

    public function index()
    {
        // Автоматически проверяется users.view
        return User::all();
    }
}
```

### Продвинутые правила

```php
protected function permissionRules(): array
{
    return [
        // Требуется хотя бы один action
        'index' => ['users.view', 'users.list'],
        
        // Требуются ВСЕ actions
        'update' => [
            'require_all' => ['users.edit', 'users.update']
        ],
        
        // Требуется ЛЮБОЙ из actions
        'show' => [
            'require_any' => ['users.view', 'users.show']
        ],
        
        // Custom callback
        'destroy' => function ($user, $model) {
            return $user->id === $model->owner_id || $user->hasAction('users.delete');
        },
        
        // Resource permissions (для конкретной записи)
        'update' => 'edit', // Автоматически проверит $folder->userCan($user, 'edit')
    ];
}
```

## Использование в контроллерах

### Проверка прав вручную

```php
public function someAction(Folder $folder)
{
    // Проверить одно право
    if ($this->can('view-folder', $folder)) {
        // Доступ разрешен
    }

    // Проверить любое из прав
    if ($this->canAny(['view-folder', 'edit-folder'], $folder)) {
        // Есть хотя бы одно право
    }

    // Проверить все права
    if ($this->canAll(['view-folder', 'edit-folder'], $folder)) {
        // Есть все права
    }

    // Убедиться в наличии права (или 403)
    $this->authorize('edit-folder', $folder);
}
```

## Глобальные правила

### В AppServiceProvider

```php
use DFiks\UnPerm\Facades\PermissionGate;

public function boot()
{
    // Суперадмин имеет все права
    PermissionGate::before(function ($user, $ability) {
        if ($user->hasAction('superadmin')) {
            return true;
        }
    });

    // Определить кастомное правило
    PermissionGate::define('manage-settings', function ($user) {
        return $user->hasRole('admin') || $user->hasGroup('managers');
    });

    // Определить правило с проверкой владельца
    PermissionGate::define('edit-post', function ($user, $post) {
        return $user->id === $post->author_id || $user->hasAction('posts.edit-any');
    });
}
```

## Helpers

```php
// В любом месте приложения
if (can_permission('users.view')) {
    // Доступ разрешен
}

// Проверить для конкретного пользователя
if (can_permission('edit-post', $post, $someUser)) {
    // $someUser может редактировать $post
}

// Убедиться в наличии права
authorize_permission('delete-post', $post); // Throw 403 if not allowed

// Проверить любое из прав
if (can_any_permission(['view-users', 'view-posts'])) {
    // Есть хотя бы одно право
}

// Проверить все права
if (can_all_permissions(['view-users', 'edit-users'])) {
    // Есть все права
}
```

## В Blade views

```blade
@if(can_permission('users.view'))
    <a href="{{ route('users.index') }}">Пользователи</a>
@endif

@if(can_permission('edit-post', $post))
    <button>Редактировать</button>
@endif

@if(can_any_permission(['view-users', 'view-posts']))
    <div>Админ панель</div>
@endif
```

## С Resource Permissions

```php
use DFiks\UnPerm\Traits\AuthorizesPermissions;
use DFiks\UnPerm\Traits\HasResourcePermissions;

// Модель с resource permissions
class Folder extends Model
{
    use HasResourcePermissions;
}

// Контроллер
class FolderController extends Controller
{
    use AuthorizesPermissions;

    protected function permissionRules(): array
    {
        return [
            'show'    => 'view',    // Проверит $folder->userCan($user, 'view')
            'update'  => 'edit',    // Проверит $folder->userCan($user, 'edit')
            'destroy' => 'delete',  // Проверит $folder->userCan($user, 'delete')
        ];
    }

    public function show(Folder $folder)
    {
        // Автоматически проверено право на просмотр конкретной $folder
        return view('folders.show', compact('folder'));
    }
}
```

## Комбинирование с Laravel Gates

```php
// Можно использовать вместе со стандартными Laravel Gates
Gate::define('update-post', function ($user, $post) {
    // Проверяем через UnPerm
    if ($user->hasAction('posts.edit-any')) {
        return true;
    }
    
    // Или владелец
    return $user->id === $post->author_id;
});
```

## Примеры реальных сценариев

### 1. CRUD контроллер с разными правами

```php
class PostController extends Controller
{
    use AuthorizesPermissions;

    protected function permissionRules(): array
    {
        return [
            'index'   => 'posts.view',
            'create'  => 'posts.create',
            'store'   => 'posts.create',
            'show'    => 'posts.view',
            'edit'    => fn($user, $post) => 
                $user->hasAction('posts.edit') || $user->id === $post->author_id,
            'update'  => fn($user, $post) => 
                $user->hasAction('posts.edit') || $user->id === $post->author_id,
            'destroy' => [
                'require_any' => ['posts.delete', 'posts.delete-own']
            ],
        ];
    }
}
```

### 2. API контроллер с role-based доступом

```php
class ApiController extends Controller
{
    use AuthorizesPermissions;

    protected function permissionRules(): array
    {
        return [
            'index'   => ['require_any' => ['api.read', 'api.full']],
            'store'   => ['require_any' => ['api.write', 'api.full']],
            'update'  => ['require_all' => ['api.write', 'api.update']],
            'destroy' => 'api.delete',
        ];
    }
}
```

### 3. Админ контроллер

```php
class AdminController extends Controller
{
    use AuthorizesPermissions;

    protected function permissionRules(): array
    {
        return [
            '*' => function ($user) {
                return $user->hasRole('admin') || $user->hasGroup('administrators');
            }
        ];
    }
}
```

## Best Practices

1. **Используйте декларативные правила** когда возможно
2. **Callbacks для сложной логики** с проверкой владельца
3. **Resource permissions** для точечного контроля
4. **Глобальные before/after** для суперадминов
5. **Helpers в views** для условного отображения
6. **Кеширование** прав пользователя для производительности

Enjoy! 🚀

