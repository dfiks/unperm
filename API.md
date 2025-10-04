# UnPerm API Documentation

Полная документация по REST API для управления системой разрешений UnPerm.

## Базовый URL

Все endpoints находятся по адресу: `/api/unperm`

## Actions API

### Получить список всех действий

```http
GET /api/unperm/actions
```

**Query параметры:**
- `search` (optional) - поиск по slug или description
- `per_page` (optional, default: 15) - количество записей на странице

**Ответ:**
```json
{
  "data": [
    {
      "id": "uuid",
      "slug": "users.view",
      "description": "View users",
      "bitmask": "1",
      "created_at": "2025-10-04T10:00:00.000000Z",
      "updated_at": "2025-10-04T10:00:00.000000Z"
    }
  ],
  "links": {...},
  "meta": {...}
}
```

### Получить конкретное действие

```http
GET /api/unperm/actions/{id}
```

**Ответ:**
```json
{
  "data": {
    "id": "uuid",
    "slug": "users.view",
    "description": "View users",
    "bitmask": "1",
    "created_at": "2025-10-04T10:00:00.000000Z",
    "updated_at": "2025-10-04T10:00:00.000000Z"
  }
}
```

### Создать новое действие

```http
POST /api/unperm/actions
```

**Body:**
```json
{
  "slug": "users.view",
  "description": "View users"
}
```

**Ответ:**
```json
{
  "message": "Action created successfully",
  "data": {
    "id": "uuid",
    "slug": "users.view",
    "description": "View users",
    "bitmask": "1",
    "created_at": "2025-10-04T10:00:00.000000Z",
    "updated_at": "2025-10-04T10:00:00.000000Z"
  }
}
```

### Обновить действие

```http
PUT /api/unperm/actions/{id}
```

**Body:**
```json
{
  "slug": "users.edit",
  "description": "Edit users"
}
```

### Удалить действие

```http
DELETE /api/unperm/actions/{id}
```

**Ответ:**
```json
{
  "message": "Action deleted successfully"
}
```

---

## Roles API

### Получить список всех ролей

```http
GET /api/unperm/roles
```

**Query параметры:**
- `search` (optional) - поиск по slug, name или description
- `per_page` (optional, default: 15) - количество записей на странице

**Ответ:**
```json
{
  "data": [
    {
      "id": "uuid",
      "slug": "admin",
      "name": "Administrator",
      "description": "System administrator",
      "bitmask": "15",
      "created_at": "2025-10-04T10:00:00.000000Z",
      "updated_at": "2025-10-04T10:00:00.000000Z",
      "actions": [...],
      "resource_actions": [...]
    }
  ]
}
```

### Получить конкретную роль

```http
GET /api/unperm/roles/{id}
```

### Создать новую роль

```http
POST /api/unperm/roles
```

**Body:**
```json
{
  "slug": "admin",
  "name": "Administrator",
  "description": "System administrator"
}
```

### Обновить роль

```http
PUT /api/unperm/roles/{id}
```

**Body:**
```json
{
  "name": "Super Administrator",
  "description": "Updated description"
}
```

### Удалить роль

```http
DELETE /api/unperm/roles/{id}
```

### Назначить действие роли

```http
POST /api/unperm/roles/{id}/actions
```

**Body:**
```json
{
  "action_id": "uuid"
}
```

**Ответ:**
```json
{
  "message": "Action attached to role successfully",
  "data": {
    "id": "uuid",
    "slug": "admin",
    "name": "Administrator",
    "actions": [...]
  }
}
```

### Убрать действие у роли

```http
DELETE /api/unperm/roles/{id}/actions/{actionId}
```

### Назначить ресурсное действие роли

```http
POST /api/unperm/roles/{id}/resource-actions
```

**Body:**
```json
{
  "resource_action_id": "uuid"
}
```

### Убрать ресурсное действие у роли

```http
DELETE /api/unperm/roles/{id}/resource-actions/{resourceActionId}
```

---

## Groups API

### Получить список всех групп

```http
GET /api/unperm/groups
```

**Query параметры:**
- `search` (optional) - поиск по slug, name или description
- `per_page` (optional, default: 15) - количество записей на странице

### Получить конкретную группу

```http
GET /api/unperm/groups/{id}
```

### Создать новую группу

```http
POST /api/unperm/groups
```

**Body:**
```json
{
  "slug": "managers",
  "name": "Managers",
  "description": "Company managers"
}
```

### Обновить группу

```http
PUT /api/unperm/groups/{id}
```

### Удалить группу

```http
DELETE /api/unperm/groups/{id}
```

### Назначить действие группе

```http
POST /api/unperm/groups/{id}/actions
```

**Body:**
```json
{
  "action_id": "uuid"
}
```

### Убрать действие у группы

```http
DELETE /api/unperm/groups/{id}/actions/{actionId}
```

### Назначить роль группе

```http
POST /api/unperm/groups/{id}/roles
```

**Body:**
```json
{
  "role_id": "uuid"
}
```

### Убрать роль у группы

```http
DELETE /api/unperm/groups/{id}/roles/{roleId}
```

### Назначить ресурсное действие группе

```http
POST /api/unperm/groups/{id}/resource-actions
```

**Body:**
```json
{
  "resource_action_id": "uuid"
}
```

### Убрать ресурсное действие у группы

```http
DELETE /api/unperm/groups/{id}/resource-actions/{resourceActionId}
```

---

## Users API

### Получить список доступных моделей пользователей

```http
GET /api/unperm/users/models
```

**Ответ:**
```json
{
  "data": [
    "App\\Models\\User",
    "App\\Domain\\Employees\\Models\\Employee"
  ]
}
```

### Получить список пользователей

```http
GET /api/unperm/users?model=App\Models\User
```

**Query параметры:**
- `model` (required) - полное имя класса модели пользователя
- `search` (optional) - поиск по имени, email и т.д.
- `per_page` (optional, default: 15) - количество записей на странице

### Получить конкретного пользователя

```http
GET /api/unperm/users/{id}?model=App\Models\User
```

**Query параметры:**
- `model` (required) - полное имя класса модели пользователя

**Ответ:**
```json
{
  "data": {
    "id": "uuid",
    "name": "John Doe",
    "email": "john@example.com",
    "actions": [...],
    "roles": [...],
    "groups": [...],
    "resource_actions": [...]
  }
}
```

### Назначить действие пользователю

```http
POST /api/unperm/users/{id}/actions
```

**Body:**
```json
{
  "model": "App\\Models\\User",
  "action_id": "uuid"
}
```

### Убрать действие у пользователя

```http
DELETE /api/unperm/users/{id}/actions/{actionId}?model=App\Models\User
```

### Назначить роль пользователю

```http
POST /api/unperm/users/{id}/roles
```

**Body:**
```json
{
  "model": "App\\Models\\User",
  "role_id": "uuid"
}
```

### Убрать роль у пользователя

```http
DELETE /api/unperm/users/{id}/roles/{roleId}?model=App\Models\User
```

### Назначить группу пользователю

```http
POST /api/unperm/users/{id}/groups
```

**Body:**
```json
{
  "model": "App\\Models\\User",
  "group_id": "uuid"
}
```

### Убрать группу у пользователя

```http
DELETE /api/unperm/users/{id}/groups/{groupId}?model=App\Models\User
```

---

## Resource Permissions API

### Получить список доступных моделей ресурсов

```http
GET /api/unperm/resource-permissions/models
```

**Ответ:**
```json
{
  "data": [
    "App\\Models\\Folder",
    "App\\Domain\\Documents\\Models\\Document"
  ]
}
```

### Получить список доступных ресурсов

```http
GET /api/unperm/resource-permissions/resources?resource_model=App\Models\Folder
```

**Query параметры:**
- `resource_model` (required) - полное имя класса модели ресурса
- `search` (optional) - поиск по названию, slug и т.д.
- `per_page` (optional, default: 15) - количество записей на странице

### Получить список ресурсных действий

```http
GET /api/unperm/resource-permissions
```

**Query параметры:**
- `resource_type` (optional) - фильтр по типу ресурса
- `resource_id` (optional) - фильтр по ID ресурса
- `action_type` (optional) - фильтр по типу действия (view, create, update, delete)
- `search` (optional) - поиск по slug, action_type или description
- `per_page` (optional, default: 15) - количество записей на странице

**Ответ:**
```json
{
  "data": [
    {
      "id": "uuid",
      "resource_type": "App\\Models\\Folder",
      "resource_id": "uuid",
      "action_type": "view",
      "slug": "folders.view.uuid",
      "bitmask": "1",
      "description": "View access to folder",
      "created_at": "2025-10-04T10:00:00.000000Z",
      "updated_at": "2025-10-04T10:00:00.000000Z"
    }
  ]
}
```

### Получить конкретное ресурсное действие

```http
GET /api/unperm/resource-permissions/{id}
```

### Предоставить доступ к ресурсу

```http
POST /api/unperm/resource-permissions/grant
```

**Body:**
```json
{
  "user_model": "App\\Models\\User",
  "user_id": "uuid",
  "resource_model": "App\\Models\\Folder",
  "resource_id": "uuid",
  "action_type": "view"
}
```

**Ответ:**
```json
{
  "message": "Permission granted successfully"
}
```

### Отозвать доступ к ресурсу

```http
POST /api/unperm/resource-permissions/revoke
```

**Body:**
```json
{
  "user_model": "App\\Models\\User",
  "user_id": "uuid",
  "resource_model": "App\\Models\\Folder",
  "resource_id": "uuid",
  "action_type": "view"
}
```

### Отозвать все доступы к ресурсу для пользователя

```http
POST /api/unperm/resource-permissions/revoke-all
```

**Body:**
```json
{
  "user_model": "App\\Models\\User",
  "user_id": "uuid",
  "resource_model": "App\\Models\\Folder",
  "resource_id": "uuid"
}
```

### Получить список пользователей с доступом к ресурсу

```http
GET /api/unperm/resource-permissions/users-with-access
```

**Query параметры:**
- `resource_model` (required) - полное имя класса модели ресурса
- `resource_id` (required) - ID ресурса
- `action_type` (required) - тип действия (view, create, update, delete)

**Ответ:**
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "John Doe",
      "email": "john@example.com",
      "model_type": "App\\Models\\User"
    }
  ]
}
```

---

## Коды ответов

- `200 OK` - Успешный запрос
- `201 Created` - Ресурс успешно создан
- `204 No Content` - Успешное удаление
- `400 Bad Request` - Неверный запрос
- `404 Not Found` - Ресурс не найден
- `422 Unprocessable Entity` - Ошибка валидации

## Формат ошибок

```json
{
  "message": "Validation failed",
  "errors": {
    "slug": ["The slug field is required."],
    "name": ["The name field is required."]
  }
}
```

## Примеры использования

### Создание роли и назначение ей действий

```bash
# 1. Создать роль
curl -X POST http://your-app.com/api/unperm/roles \
  -H "Content-Type: application/json" \
  -d '{
    "slug": "editor",
    "name": "Editor",
    "description": "Content editor"
  }'

# 2. Назначить действие
curl -X POST http://your-app.com/api/unperm/roles/{roleId}/actions \
  -H "Content-Type: application/json" \
  -d '{
    "action_id": "{actionId}"
  }'
```

### Назначение прав на конкретный ресурс

```bash
# Дать пользователю право просмотра конкретной папки
curl -X POST http://your-app.com/api/unperm/resource-permissions/grant \
  -H "Content-Type: application/json" \
  -d '{
    "user_model": "App\\Models\\User",
    "user_id": "{userId}",
    "resource_model": "App\\Models\\Folder",
    "resource_id": "{folderId}",
    "action_type": "view"
  }'
```

### Получение пользователей с доступом к ресурсу

```bash
curl "http://your-app.com/api/unperm/resource-permissions/users-with-access?resource_model=App\Models\Folder&resource_id={folderId}&action_type=view"
```

## Аутентификация

API использует стандартную аутентификацию Laravel. Убедитесь, что вы настроили соответствующие middleware для защиты endpoints.

Рекомендуется использовать:
- Laravel Sanctum для SPA и мобильных приложений
- Passport для OAuth2
- Стандартную session аутентификацию для web-приложений

## Middleware

По умолчанию все routes используют `api` middleware group. Для дополнительной защиты endpoints добавьте `auth:sanctum` или другие middleware в вашем приложении:

```php
Route::middleware(['auth:sanctum'])->group(function () {
    // UnPerm API routes
});
```

