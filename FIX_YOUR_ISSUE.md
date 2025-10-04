# 🔧 РЕШЕНИЕ ВАШЕЙ ПРОБЛЕМЫ

## Проблема найдена! ✅

Ваши `ResourceAction` записи используют **СТАРЫЙ формат slug**:

```
❌ СТАРЫЙ: "Folder:01997f25-a56c-7055-aa3e-66effafd4087:view"
✅ НОВЫЙ:  "folders.view.01997f25-a56c-7055-aa3e-66effafd4087"
```

Поэтому код не может найти права, так как ищет по новому формату!

## Быстрое решение

### Шаг 1: Проверьте что будет изменено (без применения)

```bash
php artisan unperm:migrate-resource-slugs --dry-run
```

Это покажет какие slugs будут изменены БЕЗ реального изменения.

### Шаг 2: Примените изменения

```bash
php artisan unperm:migrate-resource-slugs
```

Эта команда:
1. Найдет все ResourceActions со старым форматом
2. Преобразует их slug в новый формат 
3. Пересоздаст bitmask

### Шаг 3: Проверьте результат

```sql
SELECT id, slug, action_type, resource_type, resource_id 
FROM resource_actions 
ORDER BY created_at DESC 
LIMIT 5;
```

Теперь slug должен быть вида: `folders.view.01997f25-a56c-7055-aa3e-66effafd4087`

### Шаг 4: Проверьте что всё работает

```php
Route::get('test', function() {
    $user = \App\Domain\Employees\Models\Employee::find('01999e19-9b3d-7158-9000-16ce66e533c1');
    $folder = \App\Domain\Folders\Models\Folder::find('01997f25-a56c-7055-aa3e-66effafd4087');

    $user->load('resourceActions');
    
    dd([
        'can_view' => $folder->userCan($user, 'view'), // Должно быть TRUE
        'expected_slug' => $folder->getResourcePermissionSlug('view'),
        'actual_slug' => $user->resourceActions->first()->slug ?? 'none',
        'viewable_folders' => \App\Domain\Folders\Models\Folder::viewableBy($user)->count(),
    ]);
});
```

## Почему это произошло?

Вы создали ResourceActions когда код использовал старый формат slug. Затем код был обновлен на новый формат, но старые записи остались в БД.

## Для новых ResourceActions

Все НОВЫЕ ResourceActions (созданные после обновления кода) будут автоматически использовать правильный формат `folders.view.uuid`.

Старые нужно мигрировать один раз командой выше.

## Если что-то пошло не так

### Откатить изменения

К сожалению, команда не создает backup. Но вы можете:

1. Сделать backup БД перед запуском:
```bash
pg_dump your_database > backup_before_migration.sql
```

2. Или пересоздать ResourceActions вручную:
```php
// Удалите старые
\DFiks\UnPerm\Models\ResourceAction::where('resource_id', '01997f25-a56c-7055-aa3e-66effafd4087')->delete();

// Создайте заново
$user = \App\Domain\Employees\Models\Employee::find('01999e19-9b3d-7158-9000-16ce66e533c1');
$folder = \App\Domain\Folders\Models\Folder::find('01997f25-a56c-7055-aa3e-66effafd4087');

\DFiks\UnPerm\Support\ResourcePermission::grant($user, $folder, 'view');
```

## После миграции

Ваш API endpoint будет работать:

```php
public function index(Request $request)
{
    $user = $request->user();
    $user->load('resourceActions'); // ВАЖНО!
    
    $folders = \App\Domain\Folders\Models\Folder::viewableBy($user)->get();
    
    return response()->json($folders); // Теперь вернет папки!
}
```

## Проверка после миграции

```bash
php artisan unperm:diagnose-resources \
  "App\\Domain\\Employees\\Models\\Employee" \
  "01999e19-9b3d-7158-9000-16ce66e533c1" \
  "App\\Domain\\Folders\\Models\\Folder"
```

Должно показать:
- ✅ ResourceActions найдены
- ✅ Связь с пользователем есть
- ✅ Viewable resources > 0

