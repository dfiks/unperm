# Проверка настройки Resource Permissions

## Проблема
При раскрытии `folders.view` в админке Actions не показываются связанные Resource Actions.

## Возможные причины

### 1. Отсутствуют глобальные actions
Проверьте наличие базовых actions в таблице `actions`:
```sql
SELECT * FROM actions WHERE slug LIKE 'folders.%';
```

Должны быть записи типа:
- `folders.view`
- `folders.create`
- `folders.update`  
- `folders.delete`

**Решение:** Если их нет, нужно создать эти actions вручную или через команду sync.

### 2. ResourceActions создаются, но не находятся
Проверьте таблицу `resource_actions`:
```sql
SELECT * FROM resource_actions WHERE slug LIKE 'folders.view.%' LIMIT 5;
```

Должны быть записи типа: `folders.view.0199add9-7235-7042-baee-bcee46471acf`

### 3. Проблема в getResourcePermissionKey()
Проверьте что модель Folder использует правильный resourcePermissionKey:
- По умолчанию используется table name (getTable())
- Если у вас table называется не `folders`, нужно задать свойство `resourcePermissionKey`

### 4. Связи в pivot таблице
Проверьте `model_resource_actions`:
```sql
SELECT * FROM model_resource_actions LIMIT 5;
```

## Рекомендуемые действия

1. **Создайте базовые actions вручную** (если их нет):
```sql
INSERT INTO actions (id, name, slug, bitmask, description, created_at, updated_at)
VALUES 
  (gen_random_uuid(), 'View Folders', 'folders.view', '0', 'View folders', NOW(), NOW()),
  (gen_random_uuid(), 'Create Folders', 'folders.create', '0', 'Create folders', NOW(), NOW()),
  (gen_random_uuid(), 'Update Folders', 'folders.update', '0', 'Update folders', NOW(), NOW()),
  (gen_random_uuid(), 'Delete Folders', 'folders.delete', '0', 'Delete folders', NOW(), NOW());
```

2. **Проверьте что создаются ResourceActions**:
- Назначьте права на конкретную папку через UI
- Проверьте что появилась запись в `resource_actions`
- Проверьте что slug имеет формат `folders.view.{uuid}`

3. **Проверьте модель Folder**:
```php
class Folder extends Model
{
    use HasResourcePermissions;
    
    // Если таблица называется не "folders", укажите явно:
    protected $resourcePermissionKey = 'folders';
}
```

