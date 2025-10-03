# 🎯 Fluent API для проверки прав

UnPerm предоставляет мощный Fluent API для проверки прав доступа с возможностью цепочки вызовов и автоматического выброса исключений.

## Основное использование

### Базовая проверка с `throw()`

```php
use DFiks\UnPerm\Traits\AuthorizesPermissions;

class EmployeeController extends Controller
{
    use AuthorizesPermissions;

    public function index()
    {
        // Выбросить исключение если нет прав
        $this->can('employees.view')->throw();
        
        return Employee::all();
    }

    public function destroy(Employee $employee)
    {
        // Альтернативный синтаксис - явно указать что выбросить при отсутствии прав
        $this->can('employees.delete')->throwDenied();
        
        $employee->delete();
        
        return redirect()->route('employees.index');
    }
}
```

### Проверка с кастомным сообщением

```php
public function update(Post $post)
{
    $this->can('posts.edit')
        ->throw('Вы не можете редактировать этот пост');
    
    // Обновление поста
}
```

### Условное выполнение

```php
public function show(Document $document)
{
    $this->can('documents.view')
        ->throw()
        ->then(function () use ($document) {
            // Этот код выполнится только если есть права
            Log::info("User viewed document: {$document->id}");
        });
    
    return view('documents.show', compact('document'));
}
```

### Проверка с `else`

```php
public function index()
{
    $result = $this->can('posts.view');
    
    $result
        ->then(function () {
            // Есть права - показываем все
            $posts = Post::all();
        })
        ->else(function () {
            // Нет прав - показываем только свои
            $posts = Post::where('author_id', auth()->id())->get();
        });
    
    return view('posts.index', compact('posts'));
}
```

## Массивы прав с `canAny` и `canAll`

### canAny - хотя бы одно право

```php
public function index()
{
    // Автодополнение работает для массива!
    $this->canAny(['employees.view', 'employees.list'])->throw();
    
    return Employee::all();
}
```

### canAll - все права

```php
public function update(Employee $employee)
{
    $this->canAll(['employees.edit', 'employees.update'])
        ->throw('Недостаточно прав для редактирования');
    
    $employee->update($request->all());
}
```

### Комбинирование с условиями

```php
public function managePermissions(User $user)
{
    $result = $this->canAny([
        'users.manage-permissions',
        'admin.full-access',
        'superadmin'
    ]);
    
    if ($result->denied()) {
        abort(403, 'Access denied');
    }
    
    return view('users.permissions', compact('user'));
}
```

## Методы PermissionResult

### Проверка статуса

```php
$result = $this->can('posts.edit');

$result->allowed();  // true если разрешено
$result->denied();   // true если запрещено
$result();           // вызов как функция возвращает bool
```

### Выброс исключений

```php
$result->throw();             // Выбросить если ЗАПРЕЩЕНО (по умолчанию)
$result->throwDenied();       // Выбросить если ЗАПРЕЩЕНО
$result->throwAllowed();      // Выбросить если РАЗРЕШЕНО (редко используется)

// С кастомным сообщением
$result->throw('Недостаточно прав');
$result->throwDenied('Вы не можете это делать');
```

### Условное выполнение

```php
$result
    ->then(function () {
        // Выполнится если разрешено
    })
    ->else(function () {
        // Выполнится если запрещено
    });
```

### Получение значения

```php
// Вернуть разные значения в зависимости от результата
$message = $this->can('posts.edit')->value(
    'Вы можете редактировать',
    'Только для чтения'
);
```

### Преобразование в строку

```php
$result = $this->can('posts.edit');
echo $result;  // "ALLOWED: posts.edit" или "DENIED: posts.edit"
```

## Использование в разных местах

### В контроллерах с AuthorizesPermissions

```php
class PostController extends Controller
{
    use AuthorizesPermissions;

    protected function permissionRules(): array
    {
        return [
            'index'   => 'posts.view',
            'store'   => 'posts.create',
            'destroy' => 'posts.delete',
        ];
    }

    public function update(Post $post)
    {
        // Дополнительная проверка владельца
        $this->can('posts.edit')
            ->throw()
            ->then(function () use ($post) {
                if (auth()->id() !== $post->author_id) {
                    abort(403, 'Вы можете редактировать только свои посты');
                }
            });
        
        $post->update(request()->all());
    }
}
```

### Через Facade

```php
use DFiks\UnPerm\Facades\PermissionGate;

PermissionGate::can('users.view')->throw();

PermissionGate::canAny(['posts.edit', 'posts.update'])
    ->throwDenied('Нужны права на редактирование');
```

### Через helper функции

```php
// Базовая проверка (возвращает bool)
if (can_permission('posts.view')) {
    // ...
}

// Для fluent API используйте PermissionGate
use DFiks\UnPerm\Facades\PermissionGate;

PermissionGate::can('posts.view')->throw();
```

## Примеры из реальной жизни

### API контроллер с детальными проверками

```php
class ApiController extends Controller
{
    use AuthorizesPermissions;

    public function index()
    {
        $this->canAny(['api.read', 'api.full-access'])
            ->throw('API access required');
        
        return response()->json(Data::all());
    }

    public function store(Request $request)
    {
        $this->canAll(['api.write', 'api.create'])
            ->throw('Insufficient permissions for creating resources');
        
        $data = Data::create($request->all());
        
        return response()->json($data, 201);
    }

    public function destroy($id)
    {
        $result = $this->can('api.delete');
        
        if ($result->denied()) {
            return response()->json([
                'error' => 'Permission denied',
                'required' => 'api.delete'
            ], 403);
        }
        
        Data::destroy($id);
        
        return response()->json(null, 204);
    }
}
```

### Динамическое меню

```php
public function getMenu()
{
    $menu = [];
    
    PermissionGate::can('users.view')->then(function () use (&$menu) {
        $menu[] = ['title' => 'Users', 'url' => '/users'];
    });
    
    PermissionGate::can('posts.view')->then(function () use (&$menu) {
        $menu[] = ['title' => 'Posts', 'url' => '/posts'];
    });
    
    PermissionGate::canAny(['reports.view', 'admin'])
        ->then(function () use (&$menu) {
            $menu[] = ['title' => 'Reports', 'url' => '/reports'];
        });
    
    return $menu;
}
```

### Условная логика с разными уровнями доступа

```php
public function getDocuments()
{
    $result = $this->canAll(['documents.view', 'documents.view-all']);
    
    $documents = $result->value(
        Document::all(),                          // Если ВСЕ права - все документы
        Document::where('user_id', auth()->id())  // Если нет - только свои
            ->get()
    );
    
    return view('documents.index', compact('documents'));
}
```

### Middleware style

```php
public function handle(Request $request, Closure $next)
{
    PermissionGate::can('api.access')
        ->throw('API access token required');
    
    return $next($request);
}
```

## В Blade views

```blade
{{-- Через facade --}}
@if(\DFiks\UnPerm\Facades\PermissionGate::can('posts.create')->allowed())
    <a href="{{ route('posts.create') }}">Create Post</a>
@endif

{{-- Или через helper для bool проверки --}}
@if(can_permission('posts.create'))
    <a href="{{ route('posts.create') }}">Create Post</a>
@endif
```

## Преимущества Fluent API

1. **Читаемый код**: `$this->can('action')->throw()` понятнее чем `if (!$this->can('action')) throw ...`
2. **Цепочки вызовов**: Комбинируйте проверки и действия
3. **Меньше boilerplate**: Не нужно писать `if/else` для каждой проверки
4. **Автодополнение**: PhpStorm знает все доступные actions
5. **Гибкость**: Можно как выбросить исключение, так и выполнить callback

## Автодополнение в IDE

Для полного автодополнения запустите:

```bash
php artisan unperm:generate-ide-helper --meta
```

После этого PhpStorm будет подсказывать:
- Все доступные actions в `$this->can('')`
- Все actions в массивах `$this->canAny(['...', '...'])`
- Методы `.throw()`, `.throwDenied()`, `.then()`, etc.

🎉 Enjoy fluent permissions!

