# 📁 UI для управления Row-Level Permissions

Документация по использованию UI для управления правами на конкретные ресурсы.

## Быстрый старт

### 1. Откройте UI

Перейдите по адресу: `http://your-app.local/unperm/resources`

### 2. Что вы увидите

UI автоматически:
- ✅ Сканирует ваш проект
- ✅ Находит все модели с трейтом `HasResourcePermissions`
- ✅ Отображает список доступных ресурсов
- ✅ Позволяет управлять правами для каждой записи

## Возможности UI

### Автоматическое обнаружение моделей

UI автоматически находит все модели, использующие `HasResourcePermissions`:

```php
// Эта модель будет автоматически обнаружена
class Folder extends Model
{
    use HasUuids, HasResourcePermissions;
    
    protected $fillable = ['name', 'description'];
}
```

### Управление правами

Для каждого ресурса вы можете:

1. **Назначить права пользователю**
   - Укажите email пользователя
   - Выберите действия (view, edit, delete, etc.)
   - Нажмите "Добавить"

2. **Просмотреть текущие права**
   - Список всех пользователей с доступом
   - Какие именно действия доступны каждому

3. **Отозвать права**
   - Отозвать конкретное действие
   - Отозвать все права сразу

## Примеры использования

### Пример 1: Управление доступом к папкам

```php
// 1. Создайте модель с HasResourcePermissions
class Folder extends Model
{
    use HasUuids, HasResourcePermissions;
    
    protected $fillable = ['name', 'description'];
}

// 2. UI автоматически обнаружит её
// 3. Откройте /unperm/resources
// 4. Выберите "Folder" из списка
// 5. Выберите конкретную папку
// 6. Назначайте права!
```

### Пример 2: Документы с ограниченным доступом

```php
class Document extends Model
{
    use HasUuids, HasResourcePermissions;
    
    protected $fillable = ['title', 'content', 'author_id'];
    
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}

// В UI:
// 1. Выберите документ
// 2. Добавьте пользователей с правом "view"
// 3. Дайте автору также "edit" и "delete"
```

### Пример 3: Проекты с командным доступом

```php
class Project extends Model
{
    use HasUuids, HasResourcePermissions;
    
    protected $fillable = ['name', 'description', 'owner_id'];
}

// Workflow в UI:
// 1. Владелец создает проект
// 2. Через UI добавляет команду:
//    - user1@example.com: view, edit
//    - user2@example.com: view
//    - user3@example.com: view, edit, delete
```

## Доступные действия (Actions)

По умолчанию доступны:

- **view** - просмотр ресурса
- **edit** - редактирование
- **delete** - удаление
- **create** - создание (обычно для wildcard)
- **update** - обновление
- **archive** - архивирование

Вы можете добавить свои действия в конфигурации компонента.

## Интеграция с контроллерами

После назначения прав через UI, используйте их в коде:

```php
class FolderController extends Controller
{
    use AuthorizesPermissions;

    public function show(Folder $folder)
    {
        // Проверка прав назначенных через UI
        $this->can('folders.view')->throw();

        if (!$folder->userCan(auth()->user(), 'view')) {
            abort(403, 'Нет доступа к этой папке');
        }

        return view('folders.show', compact('folder'));
    }

    public function update(Request $request, Folder $folder)
    {
        $this->can('folders.edit')->throw();

        if (!$folder->userCan(auth()->user(), 'edit')) {
            abort(403);
        }

        $folder->update($request->validated());
        return redirect()->route('folders.show', $folder);
    }
}
```

## Поиск и фильтрация

### Поиск ресурсов

UI автоматически ищет по полям:
- `name`
- `title`
- `slug`
- `description`

```php
// Чтобы улучшить поиск, добавьте эти поля в вашу модель
class Document extends Model
{
    use HasResourcePermissions;
    
    protected $fillable = ['title', 'description', 'slug'];
}
```

### Пагинация

- 15 ресурсов на страницу
- Автоматическая пагинация
- Работает вместе с поиском

## Расширенные настройки

### Кастомные действия

Если вам нужны дополнительные действия:

```php
// В вашем контроллере или сервисе
ResourcePermission::grant($user, $document, 'publish');
ResourcePermission::grant($user, $document, 'approve');
ResourcePermission::grant($user, $document, 'archive');
```

Они автоматически появятся в списке прав пользователя.

### Кастомное отображение ресурса

```php
class Folder extends Model
{
    use HasResourcePermissions;
    
    // Переопределите для красивого отображения
    public function getNameAttribute()
    {
        return $this->title ?? "Folder #{$this->id}";
    }
}
```

### Кастомный resource key

```php
class Document extends Model
{
    use HasResourcePermissions;
    
    // По умолчанию: "documents"
    // Переопределите если нужно:
    public function getResourcePermissionKey(): string
    {
        return 'docs'; // Вместо "documents"
    }
}
```

## Troubleshooting

### Модель не отображается в списке

**Проблема**: Моя модель не появляется в UI

**Решение**:
1. Убедитесь что модель использует `HasResourcePermissions`
2. Модель должна быть в `app/Models` или других сканируемых путях
3. Проверьте что класс корректно загружается (autoload)

```php
// Правильно:
use DFiks\UnPerm\Traits\HasResourcePermissions;

class MyModel extends Model
{
    use HasResourcePermissions; // ✅
}

// Неправильно:
class MyModel extends Model
{
    // ❌ Нет трейта
}
```

### Email пользователя не найден

**Проблема**: "Пользователь с таким email не найден"

**Решение**:
- UI ищет пользователя по email во ВСЕХ моделях с `HasPermissions`
- Убедитесь что у пользователя есть поле `email`
- Проверьте что модель пользователя использует `HasPermissions` trait

### Права не применяются

**Проблема**: Назначил права через UI, но они не работают

**Решение**:

1. Проверьте что в контроллере есть проверка:
```php
if (!$resource->userCan(auth()->user(), 'view')) {
    abort(403);
}
```

2. Очистите кеш:
```bash
php artisan cache:clear
```

3. Проверьте что bitmask обновлен:
```bash
php artisan unperm:rebuild-bitmask
```

## Best Practices

### 1. Используйте осмысленные названия

```php
// ✅ Хорошо
protected $fillable = ['name', 'title'];

// ❌ Плохо
protected $fillable = ['data', 'info'];
```

### 2. Логируйте изменения прав

```php
// После назначения прав через UI
AuditLog::create([
    'user_id' => auth()->id(),
    'action' => 'grant_resource_permission',
    'details' => "Granted 'view' to {$user->email} for {$resource->name}",
]);
```

### 3. Уведомляйте пользователей

```php
// Когда пользователю дают доступ
Mail::to($user)->send(new ResourceAccessGranted($resource));
```

### 4. Периодически проверяйте права

```php
// Scheduled job для очистки устаревших прав
Schedule::command('unperm:cleanup-expired')->daily();
```

## Безопасность

### Защита UI

UI по умолчанию требует аутентификации:

```php
// routes/web.php
Route::middleware(['auth', 'can:manage-permissions'])->group(function () {
    // Ваши роуты
});
```

### Ограничение доступа к UI

```php
// В AuthServiceProvider
Gate::define('access-unperm-ui', function ($user) {
    return $user->hasRole('admin') || $user->hasAction('unperm.access');
});

// В middleware
Route::middleware(['auth', 'can:access-unperm-ui'])->group(function () {
    Route::prefix('unperm')->group(function () {
        // UI роуты
    });
});
```

### Аудит действий

Все изменения прав должны логироваться:

```php
class ManageResourcePermissions extends Component
{
    public function addUserPermission()
    {
        // ... назначение прав ...
        
        Log::info('Resource permission granted', [
            'admin_id' => auth()->id(),
            'user_id' => $user->id,
            'resource' => $resource->getResourcePermissionKey(),
            'resource_id' => $resource->getKey(),
            'actions' => $this->newUserActions,
        ]);
    }
}
```

## Заключение

UI для Row-Level Permissions предоставляет удобный способ управления доступом к конкретным записям:

- ✅ Автоматическое обнаружение моделей
- ✅ Интуитивный интерфейс
- ✅ Поиск и фильтрация
- ✅ Поддержка множества действий
- ✅ Интеграция с существующим кодом

Используйте UI для быстрого назначения прав, а код для программной проверки и обеспечения безопасности!

🎉 Готово! Теперь управление правами стало проще!

