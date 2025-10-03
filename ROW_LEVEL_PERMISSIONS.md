# 🔐 Row-Level Permissions (RLP)

Документация по использованию разрешений на уровне отдельных записей в UnPerm.

## Введение

Row-Level Permissions позволяют контролировать доступ к конкретным записям в базе данных. Например:
- Пользователь может видеть только папку с ID `abc-123`
- Сотрудник может редактировать только документ с ID `xyz-789`
- Менеджер имеет доступ ко всем проектам

## Базовая настройка

### 1. Добавьте трейт к модели

```php
use DFiks\UnPerm\Traits\HasResourcePermissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Folder extends Model
{
    use HasUuids, HasResourcePermissions;

    protected $fillable = ['name', 'description'];
}
```

### 2. Создайте действия для ресурса

Действия автоматически создаются при назначении прав:

```php
use DFiks\UnPerm\Support\ResourcePermission;

// Дать доступ пользователю к конкретной папке
$folder = Folder::find('abc-123');
$user = User::find(1);

ResourcePermission::grant($user, $folder, 'view');
// Создаст action: folders.view.abc-123

ResourcePermission::grant($user, $folder, 'edit');
// Создаст action: folders.edit.abc-123
```

## Использование в контроллерах

### Базовый контроллер с проверками

```php
use DFiks\UnPerm\Traits\AuthorizesPermissions;
use App\Models\Folder;

class FolderController extends Controller
{
    use AuthorizesPermissions;

    // Глобальные правила для всех папок
    protected function permissionRules(): array
    {
        return [
            'index'   => 'folders.view',      // Просмотр списка
            'create'  => 'folders.create',    // Создание новых
            'store'   => 'folders.create',
        ];
    }

    // Просмотр конкретной папки
    public function show(Folder $folder)
    {
        // Проверяем доступ к конкретной папке
        $this->can('folders.view')
            ->throw('У вас нет доступа к папкам');

        // Проверяем доступ к ЭТОЙ папке
        if (!$folder->userCan(auth()->user(), 'view')) {
            abort(403, 'У вас нет доступа к этой папке');
        }

        return view('folders.show', compact('folder'));
    }

    // Редактирование конкретной папки
    public function update(Request $request, Folder $folder)
    {
        // Fluent API проверка
        $this->can('folders.edit')->throw();

        // Проверка доступа к конкретной записи
        if (!$folder->userCan(auth()->user(), 'edit')) {
            abort(403);
        }

        $folder->update($request->validated());
        
        return redirect()->route('folders.show', $folder);
    }

    // Удаление
    public function destroy(Folder $folder)
    {
        $this->canAny(['folders.delete', 'admin.full'])->throw();

        if (!$folder->userCan(auth()->user(), 'delete')) {
            abort(403, 'Нельзя удалить эту папку');
        }

        $folder->delete();
        
        return redirect()->route('folders.index');
    }
}
```

### Более элегантный вариант с middleware

```php
// routes/web.php
use DFiks\UnPerm\Middleware\CheckResourcePermission;

Route::middleware(['auth'])->group(function () {
    Route::get('/folders/{folder}', [FolderController::class, 'show'])
        ->middleware('unperm:folders,view,folder');
    
    Route::put('/folders/{folder}', [FolderController::class, 'update'])
        ->middleware('unperm:folders,edit,folder');
    
    Route::delete('/folders/{folder}', [FolderController::class, 'destroy'])
        ->middleware('unperm:folders,delete,folder');
});
```

```php
class FolderController extends Controller
{
    use AuthorizesPermissions;

    protected function permissionRules(): array
    {
        return [
            'index'  => 'folders.view',
            'create' => 'folders.create',
            'store'  => 'folders.create',
        ];
    }

    // Middleware уже проверил доступ
    public function show(Folder $folder)
    {
        return view('folders.show', compact('folder'));
    }

    public function update(Request $request, Folder $folder)
    {
        $folder->update($request->validated());
        return redirect()->route('folders.show', $folder);
    }

    public function destroy(Folder $folder)
    {
        $folder->delete();
        return redirect()->route('folders.index');
    }
}
```

## Типы разрешений

### 1. Специфичное разрешение

Доступ только к конкретной записи:

```php
// folders.view.abc-123
ResourcePermission::grant($user, $folder, 'view');

// Только эта папка
$folder->userCan($user, 'view'); // true
$otherFolder->userCan($user, 'view'); // false
```

### 2. Wildcard разрешение

Доступ ко всем записям определенного действия:

```php
use DFiks\UnPerm\Models\Action;

// Создаем wildcard action
Action::create([
    'slug' => 'folders.view',  // БЕЗ ID в конце
    'name' => 'View all folders',
    'bitmask' => '0'
]);

\DFiks\UnPerm\Support\PermBit::rebuild();

$user->assignAction('folders.view');

// Теперь доступ ко ВСЕМ папкам
$folder1->userCan($user, 'view'); // true
$folder2->userCan($user, 'view'); // true
$folder3->userCan($user, 'view'); // true
```

### 3. Полный wildcard

Все действия над всеми записями:

```php
Action::create([
    'slug' => 'folders.*',
    'name' => 'Full access to folders',
    'bitmask' => '0'
]);

\DFiks\UnPerm\Support\PermBit::rebuild();

$user->assignAction('folders.*');

// Доступ ко ВСЕМУ
$folder->userCan($user, 'view');   // true
$folder->userCan($user, 'edit');   // true
$folder->userCan($user, 'delete'); // true
```

## Фильтрация данных

### Использование scope в запросах

```php
class FolderController extends Controller
{
    public function index()
    {
        // Показать только те папки, к которым есть доступ
        $folders = Folder::whereUserCan(auth()->user(), 'view')->get();
        
        return view('folders.index', compact('folders'));
    }

    public function editable()
    {
        // Только редактируемые
        $folders = Folder::editableBy(auth()->user())->get();
        
        return view('folders.editable', compact('folders'));
    }
}
```

### Предустановленные scopes

```php
// Папки которые можно просматривать
$viewable = Folder::viewableBy($user)->get();

// Папки которые можно редактировать
$editable = Folder::editableBy($user)->get();

// Папки которые можно удалять
$deletable = Folder::deletableBy($user)->get();

// Кастомный scope
$archivable = Folder::whereUserCan($user, 'archive')->get();
```

## Управление разрешениями

### Назначение прав одному пользователю

```php
use DFiks\UnPerm\Support\ResourcePermission;

$folder = Folder::find('abc-123');
$user = User::find(1);

// Одно право
ResourcePermission::grant($user, $folder, 'view');

// Несколько прав
ResourcePermission::grant($user, $folder, 'view', 'Просмотр папки ABC');
ResourcePermission::grant($user, $folder, 'edit');
ResourcePermission::grant($user, $folder, 'delete');

// CRUD сразу (create, read/view, update/edit, delete)
ResourcePermission::grantCrud($user, $folder);
```

### Назначение прав нескольким пользователям

```php
$folder = Folder::find('abc-123');
$users = User::whereIn('id', [1, 2, 3])->get();

// Дать всем доступ на просмотр
ResourcePermission::grantToMany($users, $folder, 'view');

// Дать всем CRUD
ResourcePermission::grantCrudToMany($users, $folder);
```

### Отзыв прав

```php
// Отозвать конкретное право
ResourcePermission::revoke($user, $folder, 'edit');

// Отозвать ВСЕ права на ресурс
ResourcePermission::revokeAll($user, $folder);
```

### Получение пользователей с доступом

```php
// Кто может просматривать эту папку?
$viewers = ResourcePermission::getUsersWithAccess($folder, 'view');

// Кто может редактировать?
$editors = ResourcePermission::getUsersWithAccess($folder, 'edit');
```

## Продвинутые сценарии

### Наследование прав от родительских записей

```php
class Folder extends Model
{
    use HasUuids, HasResourcePermissions;

    public function parent()
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    // Переопределяем проверку для наследования
    public function userCan($user, string $action): bool
    {
        // Сначала проверяем свои права
        if (parent::userCan($user, $action)) {
            return true;
        }

        // Если нет своих - проверяем родительскую папку
        if ($this->parent) {
            return $this->parent->userCan($user, $action);
        }

        return false;
    }
}
```

### Условные права на основе владельца

```php
class Document extends Model
{
    use HasUuids, HasResourcePermissions;

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function userCan($user, string $action): bool
    {
        // Владелец может всё
        if ($this->owner_id === $user->id) {
            return true;
        }

        // Для остальных - стандартная проверка
        return parent::userCan($user, $action);
    }
}
```

### Временные права

```php
class TemporaryAccess extends Model
{
    protected $fillable = ['user_id', 'folder_id', 'action', 'expires_at'];
    protected $casts = ['expires_at' => 'datetime'];
}

class Folder extends Model
{
    use HasUuids, HasResourcePermissions;

    public function temporaryAccesses()
    {
        return $this->hasMany(TemporaryAccess::class);
    }

    public function userCan($user, string $action): bool
    {
        // Проверяем временный доступ
        $temporaryAccess = $this->temporaryAccesses()
            ->where('user_id', $user->id)
            ->where('action', $action)
            ->where('expires_at', '>', now())
            ->exists();

        if ($temporaryAccess) {
            return true;
        }

        // Стандартная проверка
        return parent::userCan($user, $action);
    }
}
```

## Практические примеры

### Пример 1: Система управления проектами

```php
// Модель
class Project extends Model
{
    use HasUuids, HasResourcePermissions;
}

// Контроллер
class ProjectController extends Controller
{
    use AuthorizesPermissions;

    protected function permissionRules(): array
    {
        return [
            'index'  => 'projects.view',
            'create' => 'projects.create',
        ];
    }

    public function index()
    {
        // Пользователь видит только свои проекты + те, к которым дали доступ
        $projects = Project::whereUserCan(auth()->user(), 'view')
            ->orWhere('owner_id', auth()->id())
            ->get();

        return view('projects.index', compact('projects'));
    }

    public function show(Project $project)
    {
        $this->can('projects.view')->throw();

        // Владелец или есть доступ
        if ($project->owner_id !== auth()->id() && !$project->userCan(auth()->user(), 'view')) {
            abort(403);
        }

        return view('projects.show', compact('project'));
    }

    public function share(Request $request, Project $project)
    {
        // Только владелец может делиться
        if ($project->owner_id !== auth()->id()) {
            abort(403, 'Только владелец может делиться проектом');
        }

        $userToShare = User::findOrFail($request->user_id);
        
        // Даем доступ на просмотр
        ResourcePermission::grant($userToShare, $project, 'view');

        // Опционально - на редактирование
        if ($request->can_edit) {
            ResourcePermission::grant($userToShare, $project, 'edit');
        }

        return redirect()->back()->with('success', 'Доступ предоставлен');
    }
}
```

### Пример 2: Файловое хранилище

```php
class File extends Model
{
    use HasUuids, HasResourcePermissions;

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    // Права наследуются от папки
    public function userCan($user, string $action): bool
    {
        if (parent::userCan($user, $action)) {
            return true;
        }

        // Проверяем папку
        if ($this->folder) {
            return $this->folder->userCan($user, $action);
        }

        return false;
    }
}

class FileController extends Controller
{
    use AuthorizesPermissions;

    public function download(File $file)
    {
        $this->can('files.download')->throw();

        if (!$file->userCan(auth()->user(), 'download')) {
            abort(403, 'Нет доступа к файлу');
        }

        return response()->download($file->path);
    }
}
```

### Пример 3: Корпоративные документы

```php
class Document extends Model
{
    use HasUuids, HasResourcePermissions;

    const STATUS_DRAFT = 'draft';
    const STATUS_REVIEW = 'review';
    const STATUS_PUBLISHED = 'published';

    public function userCan($user, string $action): bool
    {
        // Черновики видит только автор
        if ($this->status === self::STATUS_DRAFT && $this->author_id !== $user->id) {
            return false;
        }

        // На ревью видят рецензенты
        if ($this->status === self::STATUS_REVIEW) {
            if ($this->author_id === $user->id || $user->hasRole('reviewer')) {
                return true;
            }
        }

        // Опубликованные - стандартная проверка
        return parent::userCan($user, $action);
    }
}
```

## Советы и Best Practices

### 1. Используйте кеширование

```php
// В модели
public function userCan($user, string $action): bool
{
    $cacheKey = "user:{$user->id}:can:{$this->getTable()}:{$this->getKey()}:{$action}";
    
    return cache()->remember($cacheKey, 300, function () use ($user, $action) {
        return parent::userCan($user, $action);
    });
}
```

### 2. Создавайте helper методы

```php
// В модели
public function shareWith(User $user, array $actions = ['view'])
{
    foreach ($actions as $action) {
        ResourcePermission::grant($user, $this, $action);
    }
}

public function unshareWith(User $user)
{
    ResourcePermission::revokeAll($user, $this);
}

// Использование
$folder->shareWith($user, ['view', 'edit']);
$folder->unshareWith($user);
```

### 3. Логируйте изменения прав

```php
class AuditLog extends Model
{
    protected $fillable = ['user_id', 'action', 'resource_type', 'resource_id', 'details'];
}

// При назначении прав
ResourcePermission::grant($user, $folder, 'view');

AuditLog::create([
    'user_id' => auth()->id(),
    'action' => 'grant_permission',
    'resource_type' => 'Folder',
    'resource_id' => $folder->id,
    'details' => "Granted 'view' to user {$user->email}",
]);
```

### 4. Используйте Policy классы

```php
class FolderPolicy
{
    public function view(User $user, Folder $folder): bool
    {
        return $folder->userCan($user, 'view');
    }

    public function update(User $user, Folder $folder): bool
    {
        return $folder->userCan($user, 'edit');
    }

    public function delete(User $user, Folder $folder): bool
    {
        return $folder->userCan($user, 'delete');
    }
}

// В контроллере
public function update(Request $request, Folder $folder)
{
    $this->authorize('update', $folder);
    
    $folder->update($request->validated());
}
```

## Отладка

### Проверка прав пользователя

```php
$user = User::find(1);
$folder = Folder::find('abc-123');

// Проверить доступ
$canView = $folder->userCan($user, 'view');
dd($canView); // true/false

// Посмотреть все actions пользователя
dd($user->actions->pluck('slug'));

// Посмотреть users с доступом к папке
$viewers = ResourcePermission::getUsersWithAccess($folder, 'view');
dd($viewers);
```

### Логирование проверок

```php
// В модели
public function userCan($user, string $action): bool
{
    $result = parent::userCan($user, $action);
    
    \Log::debug("Permission check", [
        'user' => $user->id,
        'resource' => $this->getTable() . ':' . $this->getKey(),
        'action' => $action,
        'result' => $result ? 'ALLOWED' : 'DENIED',
    ]);
    
    return $result;
}
```

Готово! 🎉 Теперь у вас есть полный контроль над разрешениями на уровне отдельных записей.

