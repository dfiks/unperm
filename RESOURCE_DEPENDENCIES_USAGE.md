## Доступ к зависимым ресурсам (Dependent Resources)

Этот документ описывает, как настроить и выдавать доступ к зависимым ресурсам. Пример: пароли (`passwords`) зависят от папок (`folders`), и нужно уметь выдавать доступ:

- ко всем паролям конкретной папки (включая будущие);
- только к выбранным паролям этой папки.

### 1) Предпосылки

- Модели дочерних и родительских ресурсов используют трейт `HasResourcePermissions`.
- В дочерней модели есть связь, указанная в конфиге `via` (например, `folder()` у пароля).

Пример (сокр.):
```php
class Folder extends Model { use HasResourcePermissions; protected $resourcePermissionKey = 'folders'; }
class Password extends Model {
    use HasResourcePermissions; protected $resourcePermissionKey = 'passwords';
    public function folder() { return $this->belongsTo(Folder::class, 'folder_id'); }
}
```

### 2) Конфигурация зависимостей ресурса

В `config/unperm.php` добавьте секцию для дочернего ресурса:
```php
'resource_dependencies' => [
    'passwords' => [
        'parent' => 'folders',      // ключ родителя (resource key)
        'via' => 'folder',          // связь/аксессор в дочерней модели
        'foreign_key' => 'folder_id',
        'actions' => [              // сопоставление действий
            'view' => 'view',
            'edit' => 'edit',
            'delete' => 'delete',
        ],
    ],
],
```

Это включает наследование прав: если у пользователя есть право на `folders.view` для конкретной папки, он автоматически может `passwords.view` для всех паролей этой папки (включая будущие).

### 3) Конфигурация зависимостей действий (опционально)

В `unperm.actions` можно указывать зависимости между действиями:
```php
'posts' => [
  'view' => ['name' => 'View posts'],
  'edit' => ['name' => 'Edit posts', 'depends' => ['posts.view']],
],
```
`hasAction('posts.edit')` вернёт true только если есть и `posts.edit`, и все указанные зависимости.

### 4) Сервис для выдачи прав на зависимые ресурсы

Используйте `DFiks\UnPerm\Services\ResourcePermissionService`.

- Получить текущий режим доступа для пользователя к паролям конкретной папки:
```php
$data = app(ResourcePermissionService::class)
    ->getAccessForDependentResource($user, $folder, 'passwords', 'view', \App\Models\Password::class);

// Результат:
// $data['mode'] === 'all' | 'selected'
// $data['selected_ids']   => массив ID конкретных паролей (если выбранный доступ)
```

- Установить доступ «ко всем паролям папки» (включая будущие):
```php
app(ResourcePermissionService::class)
  ->setAccessForDependentResource($user, $folder, 'passwords', 'view', 'all', \App\Models\Password::class);
```
Под капотом выдаётся `folders.view` на конкретную папку, а точечные права `passwords.view.{id}` очищаются (чтобы не дублировать).

- Установить доступ «к выбранным паролям» (без общего доступа к папке):
```php
app(ResourcePermissionService::class)
  ->setAccessForDependentResource(
      $user,
      $folder,
      'passwords',
      'view',
      'selected',
      \App\Models\Password::class,
      $selectedPasswordIds // [id1, id2, ...]
  );
```
Под капотом отзыв `folders.view` для папки и синхронизация точечных прав `passwords.view.{id}`.

### 5) Рекомендуемый API флоу для UI

1. Получить режим и выбранные элементы для отрисовки виджета:
```php
$res = app(ResourcePermissionService::class)->getAccessForDependentResource(
    $user, $folder, 'passwords', 'view', \App\Models\Password::class
);
```
2. На форме пользователь выбирает: «ко всем в папке» или «к выбранным» (+ список паролей).
3. Сохранить результат:
```php
app(ResourcePermissionService::class)->setAccessForDependentResource(
    $user, $folder, 'passwords', 'view', $mode, \App\Models\Password::class, $selectedIds
);
```

### 6) Проверка прав и выборки

- Точечная проверка: `Password::userCan($user, 'view')` учитывает и прямые права, и наследование от папки.
- Выборка доступных дочерних: `Password::viewableBy($user)` вернёт:
  - все пароли папок, на которые есть право `folders.view`;
  - плюс пароли с точечными правами `passwords.view.{id}`;
  - если нет никаких прав, вернёт пустую выборку.

### 7) Частые вопросы

- Будущие пароли в папке? Да, при `mode=all` они автоматически будут доступны, т.к. наследование происходит от `folders.view`.
- Нужны миграции для зависимостей? Нет, всё конфигурационно.
- Кеш прав? В тестах отключён; в проде работает как обычно. Сервис очищает связанные кеши автоматически при изменениях через публичные методы.


