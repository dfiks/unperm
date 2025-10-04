# Архитектура UnPerm

## Обзор системы

UnPerm - это комплексная система управления разрешениями для Laravel, использующая битовые маски для оптимизации производительности и предоставляющая гибкую систему разрешений на уровне отдельных ресурсов.

### Ключевые особенности

- **Битмаскирование**: Использование битовых операций для быстрой проверки разрешений
- **Иерархическая структура**: Actions → Roles → Groups
- **Resource Permissions**: Разрешения на уровне конкретных экземпляров моделей
- **Супер-админы**: Гибкая система определения супер-администраторов
- **Полиморфные отношения**: Поддержка множественных моделей пользователей и ресурсов
- **Кеширование**: Встроенная система кеширования битмасок
- **Sparse Bitmask**: Оптимизация для больших наборов разрешений
- **REST API**: Полноценный API для управления разрешениями
- **UI Dashboard**: Livewire-компоненты для визуального управления

---

## Структура проекта

```
unperm/
├── config/
│   └── unperm.php                          # Конфигурация системы
├── database/
│   └── migrations/                         # Миграции базы данных
│       ├── *_create_actions_table.php
│       ├── *_create_roles_table.php
│       ├── *_create_groups_table.php
│       ├── *_create_resource_actions_table.php
│       └── ...
├── routes/
│   ├── web.php                            # Web маршруты (UI)
│   └── api.php                            # API маршруты
├── src/
│   ├── Console/                           # Artisan команды
│   ├── Facades/                           # Фасады
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/                       # API контроллеры
│   │   │   └── UnPermDashboardController.php
│   │   ├── Livewire/                      # Livewire компоненты
│   │   └── Resources/                     # API ресурсы
│   ├── Middleware/                        # Middleware
│   ├── Models/                            # Eloquent модели
│   ├── Services/                          # Бизнес-логика
│   ├── Support/                           # Вспомогательные классы
│   ├── Traits/                            # Трейты для моделей
│   └── UnPermServiceProvider.php          # Service Provider
├── resources/views/                        # Blade шаблоны
└── tests/                                 # Тесты
```

---

## Архитектурные слои

### 1. Слой данных (Data Layer)

#### Основные таблицы

```
┌─────────────┐
│   actions   │  - Глобальные действия (users.view, posts.create)
└─────────────┘
       │
       ├─────────────────┐
       │                 │
┌─────────────┐   ┌─────────────┐
│    roles    │   │   groups    │  - Коллекции действий
└─────────────┘   └─────────────┘
       │                 │
       └────────┬────────┘
                │
         ┌──────────────┐
         │ User Models  │  - Любые модели с HasPermissions
         └──────────────┘
```

#### Resource-специфичные таблицы

```
┌────────────────────┐
│ resource_actions   │  - Действия на конкретные ресурсы
│                    │    (folders.view.uuid-123)
└────────────────────┘
         │
         ├─── привязка к roles
         ├─── привязка к groups
         └─── привязка к users
```

#### Pivot таблицы

- `model_actions` - Связь между моделями и глобальными действиями
- `model_resource_actions` - Связь между моделями и ресурсными действиями
- `roles_action` - Действия, принадлежащие ролям
- `groups_action` - Действия, принадлежащие группам
- `groups_roles` - Роли, принадлежащие группам
- `roles_resource_actions` - Ресурсные действия для ролей
- `groups_resource_actions` - Ресурсные действия для групп

### 2. Слой моделей (Model Layer)

#### Action (Действие)

Представляет глобальное действие в системе.

```php
class Action extends Model
{
    use HasBitmask, HasUuids;
    
    // Связи
    public function roles(): BelongsToMany
    public function groups(): BelongsToMany
}
```

**Ответственность:**
- Хранение информации о действии (slug, описание)
- Управление битмаской для быстрой проверки
- Связи с ролями и группами

#### Role (Роль)

Группирует несколько действий.

```php
class Role extends Model
{
    use HasBitmask, HasUuids;
    
    public function actions(): BelongsToMany
    public function resourceActions(): BelongsToMany
    public function groups(): BelongsToMany
}
```

**Ответственность:**
- Группировка действий
- Наследование разрешений от нескольких действий
- Поддержка ресурсных действий

#### Group (Группа)

Группирует роли и действия.

```php
class Group extends Model
{
    use HasBitmask, HasUuids;
    
    public function actions(): BelongsToMany
    public function roles(): BelongsToMany
    public function resourceActions(): BelongsToMany
}
```

**Ответственность:**
- Группировка ролей и действий
- Иерархическое наследование разрешений
- Поддержка ресурсных действий

#### ResourceAction (Ресурсное действие)

Представляет действие на конкретный экземпляр ресурса.

```php
class ResourceAction extends Model
{
    use HasBitmask, HasUuids;
    
    public static function findOrCreateForResource($resource, string $actionType): self
    public function getResourceClassName(): string
    public function getUsersCount(): int
}
```

**Ответственность:**
- Хранение информации о действии на конкретный ресурс
- Генерация уникального slug (например, `folders.view.uuid-123`)
- Подсчет пользователей с доступом

### 3. Слой трейтов (Traits Layer)

#### HasPermissions

Основной трейт для моделей пользователей.

```php
trait HasPermissions
{
    // Прямые действия
    public function actions(): MorphToMany
    public function assignAction($action): void
    public function removeAction($action): void
    public function hasAction(string|Action $action): bool
    
    // Роли
    public function roles(): MorphToMany
    public function assignRole(Role $role): void
    public function hasRole(string|Role $role): bool
    
    // Группы
    public function groups(): MorphToMany
    public function assignGroup(Group $group): void
    public function hasGroup(string|Group $group): bool
    
    // Ресурсные действия
    public function resourceActions(): MorphToMany
    public function getAllResourceActions(): Collection
    
    // Битмаски
    public function getPermissionBitmask(): string
    public function calculatePermissionBitmask(): string
    
    // Супер-админ
    public function isSuperAdmin(): bool
}
```

**Ответственность:**
- Управление разрешениями пользователя
- Связи с actions, roles, groups
- Вычисление агрегированной битмаски
- Проверка разрешений

#### HasResourcePermissions

Трейт для моделей ресурсов (папки, документы и т.д.).

```php
trait HasResourcePermissions
{
    // Конфигурация
    protected string $resourcePermissionKey = 'resource_name';
    
    // Проверка разрешений
    public function userCan(Model $user, string $action): bool
    public function userHasActionBySlug(Model $user, string $slug): bool
    
    // Scope для фильтрации
    public function scopeWhereUserCan(Builder $query, Model $user, string $action): Builder
    public function scopeViewableBy(Builder $query, Model $user): Builder
    
    // Генерация slug
    public function getResourcePermissionSlug(string $action): string
}
```

**Ответственность:**
- Проверка прав доступа к конкретному ресурсу
- Фильтрация запросов по правам пользователя
- Генерация корректных slug для проверки

#### HasBitmask

Управление битовыми масками.

```php
trait HasBitmask
{
    protected static function bootHasBitmask(): void
    public function assignBit(): void
    public function getBit(): int
    public static function getMaxBit(): int
}
```

**Ответственность:**
- Автоматическое назначение уникальных битов
- Управление битмасками
- Отслеживание максимального бита

#### HasSparseBitmask

Оптимизация для больших наборов разрешений (>64 бита).

```php
trait HasSparseBitmask
{
    public function getSparsePermissionBitmask(): array
    public function hasPermissionInSegment(int $segment, string $checkMask): bool
}
```

**Ответственность:**
- Разделение битмаски на сегменты
- Оптимизация памяти для больших наборов
- Проверка разрешений в сегментах

### 4. Слой сервисов (Service Layer)

#### PermissionChecker

Центральный сервис проверки разрешений.

```php
class PermissionChecker
{
    public function userHasAction(Model $user, string|Action $action): bool
    public function userCan(Model $user, string $permission): bool
}
```

**Ответственность:**
- Централизованная проверка разрешений
- Интеграция с супер-админами
- Кеширование результатов

#### ModelDiscovery

Поиск моделей с определенными трейтами.

```php
class ModelDiscovery
{
    public function findModelsWithPermissions(): array
    public function findModelsWithResourcePermissions(): array
    public function getModelsWithTrait(string $traitName): array
}
```

**Ответственность:**
- Сканирование проекта для поиска моделей
- Кеширование результатов
- Определение моделей с разрешениями

#### SuperAdminChecker

Определение супер-администраторов.

```php
class SuperAdminChecker
{
    public static function isSuperAdmin(Model $user): bool
    public function check(Model $user): bool
    public function getReason(Model $user): ?string
    
    // Проверки
    protected function checkByModel(Model $user, array $config): bool
    protected function checkById(Model $user, array $config): bool
    protected function checkByEmail(Model $user, array $config): bool
    protected function checkByUsername(Model $user, array $config): bool
    protected function checkByMethod(Model $user, array $config): bool
    protected function checkByAction(Model $user, array $config): bool
    protected function checkByCallback(Model $user, array $config): bool
}
```

**Ответственность:**
- Определение супер-админов по различным критериям
- Гибкая конфигурация
- Отладка (getReason)

### 5. Слой поддержки (Support Layer)

#### BitmaskOptimizer

Оптимизация битмасок.

```php
class BitmaskOptimizer
{
    public function optimize(string $bitmask): string
    public function calculateForModel(Model $model): string
}
```

#### BitmaskCache

Кеширование битмасок.

```php
class BitmaskCache
{
    public static function getUserBitmask(Model $user): string
    public static function clearUserBitmask(Model $user): void
}
```

#### ResourcePermission

Управление разрешениями на ресурсы.

```php
class ResourcePermission
{
    public static function grant(Model $user, Model $resource, string $action): void
    public static function revoke(Model $user, Model $resource, string $action): void
    public static function revokeAll(Model $user, Model $resource): void
    public static function getUsersWithAccess(Model $resource, string $action): Collection
}
```

**Ответственность:**
- Предоставление доступа к ресурсам
- Отзыв доступа
- Получение списка пользователей с доступом

#### PermissionGate (Fluent API)

Удобный API для проверки разрешений.

```php
class PermissionGate
{
    public function forUser(Model $user): self
    public function can(string $action): PermissionResult
    public function canAll(array $actions): PermissionResult
    public function canAny(array $actions): PermissionResult
    public function hasRole(string $role): PermissionResult
    public function hasGroup(string $group): PermissionResult
}
```

**Использование:**
```php
PermissionGate::forUser($user)
    ->can('users.view')
    ->check(); // true/false
```

#### PermissionResult

Результат проверки разрешений.

```php
class PermissionResult
{
    public function check(): bool
    public function authorize(): void
    public function getReason(): string
}
```

---

## Поток данных

### 1. Проверка глобального разрешения

```
User → hasAction('users.view')
  ↓
Проверка isSuperAdmin()
  ↓ (нет)
Проверка прямых actions
  ↓ (нет)
Проверка через roles
  ↓ (нет)
Проверка через groups
  ↓ (нет)
Проверка через bitmask
  ↓
Результат: true/false
```

### 2. Проверка ресурсного разрешения

```
Resource → userCan($user, 'view')
  ↓
Проверка isSuperAdmin()
  ↓ (нет)
Генерация slug: "folders.view.uuid-123"
  ↓
Поиск в resourceActions пользователя
  ↓ (нет)
Поиск через roles пользователя
  ↓ (нет)
Поиск через groups пользователя
  ↓ (нет)
Проверка getAllResourceActions()
  ↓
Результат: true/false
```

### 3. Scope фильтрация

```
Folder::viewableBy($user)->get()
  ↓
Проверка isSuperAdmin()
  ↓ (да)
Возврат всех записей
  ↓ (нет)
Получение всех ResourceAction для пользователя
  ↓
Фильтрация только по resource_id из actions
  ↓
Возврат отфильтрованного QueryBuilder
```

---

## Система битмаскирования

### Принцип работы

Каждому действию присваивается уникальный бит (0, 1, 2, 3...).

```
Действие        | Бит | Битмаска
----------------|-----|----------
users.view      |  0  | 0000001 (1)
users.create    |  1  | 0000010 (2)
users.update    |  2  | 0000100 (4)
users.delete    |  3  | 0001000 (8)
posts.view      |  4  | 0010000 (16)
```

### Агрегация битмасок

Роль с `users.view` (1) и `users.create` (2):
```
  0000001 (users.view)
| 0000010 (users.create)
---------
  0000011 = 3
```

Пользователь с ролью и прямым действием `users.update` (4):
```
  0000011 (роль)
| 0000100 (прямое действие)
---------
  0000111 = 7
```

### Проверка разрешения

Для проверки `users.create` (бит 1, маска 2):
```
Битмаска пользователя: 0000111 (7)
Битмаска действия:      0000010 (2)
AND операция:           0000010 (2)

Результат AND == Битмаска действия?
2 == 2 → true (есть разрешение)
```

### Sparse Bitmask

Для более 64 действий битмаска разделяется на сегменты:

```
Сегмент 0: биты 0-63
Сегмент 1: биты 64-127
Сегмент 2: биты 128-191
...
```

Хранение:
```php
[
    0 => "18446744073709551615", // сегмент 0
    1 => "2251799813685247",     // сегмент 1
    2 => "0"                      // сегмент 2
]
```

---

## REST API Architecture

### Структура API

```
/api/unperm/
├── actions/              # CRUD для действий
├── roles/                # CRUD для ролей
│   ├── {id}/actions      # Управление действиями роли
│   └── {id}/resource-actions
├── groups/               # CRUD для групп
│   ├── {id}/actions
│   ├── {id}/roles
│   └── {id}/resource-actions
├── users/                # Управление пользователями
│   ├── models            # Список моделей
│   ├── {id}/actions
│   ├── {id}/roles
│   └── {id}/groups
└── resource-permissions/ # Управление ресурсными разрешениями
    ├── models            # Список моделей ресурсов
    ├── resources         # Список ресурсов
    ├── grant             # Предоставление доступа
    ├── revoke            # Отзыв доступа
    └── users-with-access # Пользователи с доступом
```

### API Resources

Все ответы форматируются через JSON Resources:
- `ActionResource`
- `RoleResource`
- `GroupResource`
- `ResourceActionResource`

### Контроллеры

- `ActionsApiController` - CRUD для действий
- `RolesApiController` - CRUD для ролей + управление связями
- `GroupsApiController` - CRUD для групп + управление связями
- `UsersApiController` - Управление разрешениями пользователей
- `ResourcePermissionsApiController` - Управление ресурсными разрешениями

---

## UI Layer (Livewire)

### Компоненты

#### ManageActions
- Просмотр всех действий
- CRUD операции
- Отображение связанных ResourceAction
- Создание глобального Action из "осиротевших" ResourceAction

#### ManageRoles
- Просмотр всех ролей
- CRUD операции
- Управление действиями роли
- Управление ресурсными действиями роли (модальное окно)

#### ManageGroups
- Просмотр всех групп
- CRUD операции
- Управление действиями и ролями группы
- Управление ресурсными действиями группы

#### ManageUserPermissions
- Выбор модели пользователя
- Просмотр разрешений пользователя
- Управление прямыми действиями, ролями, группами

#### ManageResourcePermissions
- Выбор типа ресурса
- Выбор конкретного ресурса
- Управление доступом пользователей к ресурсу
- Отображение текущих разрешений

### Layout

```
┌─────────────────────────────────────────┐
│  Sidebar (фиолетовый)   │   Content     │
│                         │               │
│  • Dashboard            │  ┌──────────┐ │
│  • Actions              │  │  Card    │ │
│  • Roles                │  │  Content │ │
│  • Groups               │  └──────────┘ │
│  • Users                │               │
│  • Resources            │               │
└─────────────────────────────────────────┘
```

---

## Console Commands

### SyncActionsCommand
Синхронизирует действия из конфигурации в БД.

### SyncRolesCommand
Синхронизирует роли из конфигурации в БД.

### SyncGroupsCommand
Синхронизирует группы из конфигурации в БД.

### SyncPermissionsCommand
Запускает все sync команды разом.

### RebuildBitmaskCommand
Пересчитывает битмаски для всех моделей.

### GenerateIdeHelperCommand
Генерирует PHPDoc для IDE автодополнения.

### ListModelsCommand
Показывает модели с трейтами HasPermissions/HasResourcePermissions.

### DiagnoseResourcePermissionsCommand
Диагностика проблем с ресурсными разрешениями.

### MigrateResourceActionSlugsCommand
Миграция старых slug к новому формату.

### AnalyzeBitmaskCommand
Анализ использования битмасок, поиск конфликтов.

---

## Middleware

### CheckResourcePermission

Проверяет разрешения перед выполнением действия.

```php
Route::get('/folders/{folder}', [FolderController::class, 'show'])
    ->middleware('unperm:folders,view');
```

**Ответственность:**
- Извлечение ресурса из route
- Проверка разрешения через `userCan()`
- Возврат 403 при отсутствии доступа

---

## Конфигурация

### config/unperm.php

```php
return [
    // Кеширование
    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
        'cache_user_bitmasks' => true,
        'cache_role_bitmasks' => true,
    ],
    
    // Супер-админы
    'superadmins' => [
        'enabled' => true,
        'models' => [],
        'ids' => [],
        'emails' => [],
        'check_method' => null,
        'action' => null,
        'callback' => null,
    ],
    
    // Actions, Roles, Groups
    'actions' => [],
    'roles' => [],
    'groups' => [],
    
    // UI
    'routes' => [
        'enabled' => true,
        'prefix' => 'unperm',
        'middleware' => ['web', 'auth'],
    ],
];
```

---

## Паттерны проектирования

### 1. Repository Pattern
`Models` выступают как репозитории для доступа к данным.

### 2. Service Layer Pattern
`Services/` содержат бизнес-логику, отделенную от контроллеров.

### 3. Trait Composition
Функциональность распределена по трейтам для переиспользования.

### 4. Facade Pattern
`Facades/PermissionGate` и `Facades/UnPerm` для удобного доступа.

### 5. Strategy Pattern
`SuperAdminChecker` использует различные стратегии проверки.

### 6. Fluent Interface
`PermissionGate` предоставляет fluent API.

### 7. Observer Pattern
События модели для автоматической очистки кеша.

### 8. Factory Pattern
`ResourceAction::findOrCreateForResource()` - factory method.

---

## Производительность

### Оптимизации

1. **Битмаски вместо JOIN'ов**
   - Проверка разрешения через битовые операции O(1)
   - Вместо множественных JOIN'ов

2. **Кеширование**
   - Кеш битмасок пользователей
   - Кеш списка моделей
   - TTL конфигурируется

3. **Eager Loading**
   - API автоматически загружает связи
   - `with(['actions', 'roles', 'groups'])`

4. **Sparse Bitmask**
   - Для >64 действий
   - Экономия памяти

5. **Индексы БД**
   - На slug полях
   - На внешних ключах
   - На composite keys в pivot таблицах

### Benchmarks

```
Проверка разрешения (без кеша):     ~0.5ms
Проверка разрешения (с кешем):      ~0.1ms
Scope фильтрация 1000 записей:      ~50ms
Расчет битмаски пользователя:       ~2ms
```

---

## Расширяемость

### Добавление новой модели пользователя

```php
class Employee extends Model
{
    use HasPermissions;
}
```

### Добавление новой модели ресурса

```php
class Document extends Model
{
    use HasResourcePermissions;
    
    protected string $resourcePermissionKey = 'documents';
}
```

### Кастомный SuperAdmin чекер

```php
'superadmins' => [
    'callback' => function ($user) {
        return $user->is_owner && $user->company->is_premium;
    },
],
```

### Кастомная проверка разрешений

```php
class CustomPermissionChecker extends PermissionChecker
{
    public function userHasAction(Model $user, string|Action $action): bool
    {
        // Кастомная логика
        return parent::userHasAction($user, $action);
    }
}
```

---

## Безопасность

### Защита от инъекций

- Все входные данные валидируются
- Использование prepared statements (Eloquent)
- Параметризованные запросы

### Проверка прав

- Супер-админы всегда проверяются первыми
- Невозможно обойти проверку разрешений
- Middleware для защиты маршрутов

### Аудит

- Timestamps на всех таблицах
- Возможность логирования изменений разрешений

---

## Тестирование

### Unit тесты

- `ActionModelTest` - тесты модели Action
- `RoleModelTest` - тесты модели Role
- `GroupModelTest` - тесты модели Group
- `PermBitTest` - тесты битовых операций
- `BitmaskOptimizerTest` - тесты оптимизатора

### Feature тесты

- `HasPermissionsTest` - тесты трейта
- `ResourcePermissionsTest` - тесты ресурсных разрешений
- `PermissionGateFluentTest` - тесты fluent API
- `SyncCommandsTest` - тесты консольных команд

### Интеграционные тесты

- `FullWorkflowTest` - полный цикл работы системы
- `ResourcePermissionsViewableTest` - тесты scope фильтрации

---

## Диаграммы

### Диаграмма классов (упрощенная)

```
┌──────────────┐
│    Action    │
└──────────────┘
       △
       │ uses
       │
┌──────────────┐         ┌──────────────┐
│     Role     │────────▷│   HasBitmask │
└──────────────┘         └──────────────┘
       △
       │ uses
       │
┌──────────────┐
│    Group     │
└──────────────┘
       △
       │ belongs to
       │
┌──────────────┐         ┌────────────────────┐
│  User Model  │────────▷│  HasPermissions    │
└──────────────┘         └────────────────────┘
       │
       │ uses
       ▽
┌──────────────┐         ┌────────────────────────┐
│   Resource   │────────▷│ HasResourcePermissions │
└──────────────┘         └────────────────────────┘
```

### Диаграмма потока данных

```
┌─────────────┐
│  HTTP API   │
└─────────────┘
       │
       ▽
┌─────────────┐
│ Controller  │
└─────────────┘
       │
       ▽
┌─────────────┐
│  Service    │───────▷ Cache
└─────────────┘
       │
       ▽
┌─────────────┐
│    Model    │───────▷ Database
└─────────────┘
       │
       ▽
┌─────────────┐
│   Trait     │
└─────────────┘
```

---

## Будущие улучшения

### Планируемые функции

1. **GraphQL API** - альтернатива REST API
2. **Temporal Permissions** - разрешения с временными рамками
3. **Permission Templates** - шаблоны разрешений
4. **Audit Log** - полное логирование изменений
5. **Permission Inheritance Visualization** - визуализация наследования
6. **Bulk Operations** - массовые операции через API
7. **Permission Export/Import** - экспорт/импорт конфигурации
8. **Multi-tenancy Support** - поддержка мультитенантности
9. **WebSocket Events** - real-time уведомления об изменениях
10. **Advanced Caching** - более продвинутое кеширование (Redis tags)

---

## Зависимости

### Основные

- `laravel/framework` (^10.0|^11.0)
- `livewire/livewire` (^3.0) - для UI
- `ext-gmp` - для битовых операций

### Dev зависимости

- `phpunit/phpunit` - тестирование
- `orchestra/testbench` - тестовое окружение

---

## Лицензия и поддержка

Проект является внутренним пакетом для DFiks.

**Разработчик**: DFiks Team  
**Версия**: 1.0.0  
**Laravel**: 10.x, 11.x  
**PHP**: 8.1+

---

## Заключение

UnPerm представляет собой комплексную, высокопроизводительную систему управления разрешениями с поддержкой как глобальных, так и ресурс-специфичных разрешений. Архитектура построена на принципах SOLID, использует проверенные паттерны проектирования и оптимизирована для высокой производительности через битмаскирование и кеширование.

Система легко расширяется, поддерживает множественные модели пользователей и ресурсов, и предоставляет удобные интерфейсы как для программного использования (API, трейты), так и для визуального управления (Livewire UI).

