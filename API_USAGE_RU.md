# UnPerm API - Краткое руководство

## Введение

API UnPerm предоставляет полный набор endpoints для управления системой разрешений через HTTP запросы.

Все API endpoints находятся по адресу: `/api/unperm/`

## Быстрый старт

### 1. Получение списка действий

```bash
GET /api/unperm/actions
```

С поиском:
```bash
GET /api/unperm/actions?search=users&per_page=20
```

### 2. Создание роли

```bash
POST /api/unperm/roles
Content-Type: application/json

{
  "slug": "manager",
  "name": "Менеджер",
  "description": "Роль менеджера компании"
}
```

### 3. Назначение действия роли

```bash
POST /api/unperm/roles/{roleId}/actions
Content-Type: application/json

{
  "action_id": "uuid-действия"
}
```

### 4. Предоставление доступа к конкретному ресурсу

```bash
POST /api/unperm/resource-permissions/grant
Content-Type: application/json

{
  "user_model": "App\\Domain\\Employees\\Models\\Employee",
  "user_id": "01999e19-9b3d-7158-9000-16ce66e533c1",
  "resource_model": "App\\Domain\\Folders\\Models\\Folder",
  "resource_id": "01997f25-a56c-7055-aa3e-66effafd4087",
  "action_type": "view"
}
```

## Основные группы endpoints

### Actions (Действия)
- `GET /api/unperm/actions` - Список всех действий
- `GET /api/unperm/actions/{id}` - Конкретное действие
- `POST /api/unperm/actions` - Создать действие
- `PUT /api/unperm/actions/{id}` - Обновить действие
- `DELETE /api/unperm/actions/{id}` - Удалить действие

### Roles (Роли)
- `GET /api/unperm/roles` - Список всех ролей
- `POST /api/unperm/roles` - Создать роль
- `POST /api/unperm/roles/{id}/actions` - Назначить действие роли
- `DELETE /api/unperm/roles/{id}/actions/{actionId}` - Убрать действие у роли
- `POST /api/unperm/roles/{id}/resource-actions` - Назначить ресурсное действие роли
- `DELETE /api/unperm/roles/{id}/resource-actions/{resourceActionId}` - Убрать ресурсное действие

### Groups (Группы)
- `GET /api/unperm/groups` - Список всех групп
- `POST /api/unperm/groups` - Создать группу
- `POST /api/unperm/groups/{id}/actions` - Назначить действие группе
- `POST /api/unperm/groups/{id}/roles` - Назначить роль группе
- `POST /api/unperm/groups/{id}/resource-actions` - Назначить ресурсное действие группе

### Users (Пользователи)
- `GET /api/unperm/users/models` - Список доступных моделей пользователей
- `GET /api/unperm/users?model=App\Models\User` - Список пользователей
- `GET /api/unperm/users/{id}?model=App\Models\User` - Конкретный пользователь
- `POST /api/unperm/users/{id}/actions` - Назначить действие пользователю
- `POST /api/unperm/users/{id}/roles` - Назначить роль пользователю
- `POST /api/unperm/users/{id}/groups` - Назначить группу пользователю

### Resource Permissions (Разрешения на ресурсы)
- `GET /api/unperm/resource-permissions/models` - Список доступных моделей ресурсов
- `GET /api/unperm/resource-permissions/resources` - Список доступных ресурсов
- `GET /api/unperm/resource-permissions` - Список ресурсных действий
- `POST /api/unperm/resource-permissions/grant` - Предоставить доступ к ресурсу
- `POST /api/unperm/resource-permissions/revoke` - Отозвать доступ к ресурсу
- `POST /api/unperm/resource-permissions/revoke-all` - Отозвать все доступы
- `GET /api/unperm/resource-permissions/users-with-access` - Пользователи с доступом к ресурсу

## Практические примеры

### Пример 1: Создание полной иерархии разрешений

```php
// 1. Создаем действие
$action = Http::post('/api/unperm/actions', [
    'slug' => 'documents.view',
    'description' => 'Просмотр документов'
]);

// 2. Создаем роль
$role = Http::post('/api/unperm/roles', [
    'slug' => 'document-viewer',
    'name' => 'Просмотр документов',
    'description' => 'Может просматривать документы'
]);

// 3. Назначаем действие роли
Http::post("/api/unperm/roles/{$role['data']['id']}/actions", [
    'action_id' => $action['data']['id']
]);

// 4. Назначаем роль пользователю
Http::post("/api/unperm/users/{$userId}/roles", [
    'model' => 'App\\Models\\User',
    'role_id' => $role['data']['id']
]);
```

### Пример 2: Управление доступом к конкретным папкам

```php
// Получаем список всех папок
$folders = Http::get('/api/unperm/resource-permissions/resources', [
    'resource_model' => 'App\\Domain\\Folders\\Models\\Folder',
    'per_page' => 50
]);

// Даем доступ к конкретной папке
Http::post('/api/unperm/resource-permissions/grant', [
    'user_model' => 'App\\Domain\\Employees\\Models\\Employee',
    'user_id' => $employeeId,
    'resource_model' => 'App\\Domain\\Folders\\Models\\Folder',
    'resource_id' => $folderId,
    'action_type' => 'view'
]);

// Проверяем кто имеет доступ к папке
$users = Http::get('/api/unperm/resource-permissions/users-with-access', [
    'resource_model' => 'App\\Domain\\Folders\\Models\\Folder',
    'resource_id' => $folderId,
    'action_type' => 'view'
]);
```

### Пример 3: Групповое назначение разрешений

```php
// 1. Создаем группу
$group = Http::post('/api/unperm/groups', [
    'slug' => 'sales-team',
    'name' => 'Отдел продаж',
    'description' => 'Сотрудники отдела продаж'
]);

// 2. Назначаем несколько действий группе
$actions = ['clients.view', 'clients.create', 'deals.view', 'deals.create'];
foreach ($actions as $actionSlug) {
    $action = Action::where('slug', $actionSlug)->first();
    Http::post("/api/unperm/groups/{$group['data']['id']}/actions", [
        'action_id' => $action->id
    ]);
}

// 3. Добавляем пользователей в группу
$salesEmployees = [/* массив ID сотрудников */];
foreach ($salesEmployees as $employeeId) {
    Http::post("/api/unperm/users/{$employeeId}/groups", [
        'model' => 'App\\Domain\\Employees\\Models\\Employee',
        'group_id' => $group['data']['id']
    ]);
}
```

### Пример 4: Работа с ресурсными действиями через роли

```php
// Получаем конкретную папку
$folder = Folder::find($folderId);

// Создаем или получаем ResourceAction для этой папки
$resourceAction = ResourceAction::findOrCreateForResource($folder, 'view');

// Назначаем ResourceAction роли
Http::post("/api/unperm/roles/{$roleId}/resource-actions", [
    'resource_action_id' => $resourceAction->id
]);

// Теперь все пользователи с этой ролью имеют доступ к папке
```

## Поиск и фильтрация

Все list endpoints поддерживают поиск и пагинацию:

```bash
# Поиск действий
GET /api/unperm/actions?search=users&per_page=10

# Поиск ролей
GET /api/unperm/roles?search=admin&per_page=25

# Фильтрация ресурсных действий
GET /api/unperm/resource-permissions?resource_type=App\Models\Folder&action_type=view

# Поиск пользователей
GET /api/unperm/users?model=App\Models\User&search=john&per_page=15
```

## Обработка ошибок

API возвращает стандартные HTTP коды:

```php
try {
    $response = Http::post('/api/unperm/actions', $data);
    
    if ($response->successful()) {
        // 200-299
        return $response->json();
    }
    
    if ($response->status() === 422) {
        // Ошибка валидации
        $errors = $response->json()['errors'];
        // Обработка ошибок валидации
    }
    
    if ($response->status() === 404) {
        // Ресурс не найден
    }
    
} catch (\Exception $e) {
    // Обработка исключений
}
```

## Аутентификация

Не забудьте настроить аутентификацию для API endpoints:

```php
// В вашем RouteServiceProvider или routes/api.php
Route::middleware(['auth:sanctum'])->group(function () {
    // UnPerm API будет защищен аутентификацией
});
```

Или для конкретных моделей пользователей:

```php
Route::middleware(['auth:employees'])->group(function () {
    // Доступ только для сотрудников
});
```

## Производительность

Для оптимизации производительности:

1. **Используйте пагинацию**: всегда указывайте `per_page`
2. **Кешируйте результаты**: если данные не меняются часто
3. **Используйте фильтры**: уменьшайте объем данных через query параметры
4. **Eager loading**: API автоматически загружает связанные данные где необходимо

## Дополнительная информация

Полная документация по всем endpoints: [API.md](./API.md)

Примеры использования системы разрешений: [USAGE.md](./USAGE.md)

Работа с ресурсными разрешениями: [ROW_LEVEL_PERMISSIONS.md](./ROW_LEVEL_PERMISSIONS.md)

