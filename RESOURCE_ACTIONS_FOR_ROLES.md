# Resource Actions для Ролей и Групп

## Что было добавлено

### 1. Миграции
- `2024_01_01_000014_create_roles_resource_actions_table.php` - связь ролей с ResourceActions
- `2024_01_01_000015_create_groups_resource_actions_table.php` - связь групп с ResourceActions

### 2. Связи в моделях
- `Role::resourceActions()` - связь Many-to-Many с ResourceAction
- `Group::resourceActions()` - связь Many-to-Many с ResourceAction

## Как использовать

### Назначить ResourceAction роли программно

```php
use DFiks\UnPerm\Models\Role;
use DFiks\UnPerm\Models\ResourceAction;
use App\Domain\Folders\Models\Folder;

$role = Role::find('role-uuid');
$folder = Folder::find('folder-uuid');

// Создаем или находим ResourceAction
$resourceAction = ResourceAction::findOrCreateForResource($folder, 'view');

// Назначаем роли
$role->resourceActions()->attach($resourceAction->id);
```

### Получить все права роли (глобальные + ресурсные)

```php
$role = Role::with(['actions', 'resourceActions'])->find('role-uuid');

// Глобальные права
foreach ($role->actions as $action) {
    echo "Global: {$action->slug}\n";
}

// Права на конкретные ресурсы
foreach ($role->resourceActions as $resourceAction) {
    echo "Resource: {$resourceAction->slug}\n";
    // Например: folders.view.uuid-123
}
```

### Проверка прав пользователя с учетом ролей

Нужно обновить метод вычисления прав пользователя чтобы учитывал resourceActions из ролей.

В `HasPermissions` trait добавить:

```php
public function getAllResourceActionsFromRolesAndGroups()
{
    $resourceActions = collect([]);
    
    // Из ролей
    foreach ($this->roles as $role) {
        $resourceActions = $resourceActions->merge($role->resourceActions);
    }
    
    // Из групп
    foreach ($this->groups as $group) {
        $resourceActions = $resourceActions->merge($group->resourceActions);
        
        // Из ролей внутри групп
        foreach ($group->roles as $role) {
            $resourceActions = $resourceActions->merge($role->resourceActions);
        }
    }
    
    return $resourceActions->unique('id');
}
```

## UI для управления (TODO)

Для полноценного UI нужно:

1. **В ManageRoles добавить секцию "Resource Permissions"**
   - Показать список назначенных ResourceActions
   - Кнопка "Add Resource Permission"
   - Модальное окно с выбором:
     * Тип ресурса (Folder, Project, etc.)
     * Конкретный ресурс (выпадающий список)
     * Действие (view, edit, delete, custom)

2. **Фильтрация ресурсов**
   - При выборе типа ресурса загружать список доступных
   - Поиск по названию
   - Пагинация для больших списков

3. **Отображение**
   - В таблице ролей показывать количество ResourceActions
   - При раскрытии роли - список всех назначенных прав с возможностью удаления

## Пример реализации UI (упрощенный)

Создайте отдельный Livewire компонент `ManageRoleResourcePermissions`:

```php
class ManageRoleResourcePermissions extends Component
{
    public $roleId;
    public $showModal = false;
    public $selectedResourceType;
    public $selectedResourceId;
    public $selectedAction = 'view';
    public $availableResources = [];
    
    public function mount($roleId)
    {
        $this->roleId = $roleId;
    }
    
    public function updatedSelectedResourceType()
    {
        // Загружаем ресурсы этого типа
        if (class_exists($this->selectedResourceType)) {
            $this->availableResources = $this->selectedResourceType::select('id', 'name')
                ->limit(100)
                ->get()
                ->toArray();
        }
    }
    
    public function addResourcePermission()
    {
        $role = Role::find($this->roleId);
        $resource = $this->selectedResourceType::find($this->selectedResourceId);
        
        if ($resource) {
            $resourceAction = ResourceAction::findOrCreateForResource(
                $resource,
                $this->selectedAction
            );
            
            $role->resourceActions()->syncWithoutDetaching([$resourceAction->id]);
            
            session()->flash('message', 'Resource permission added!');
            $this->closeModal();
        }
    }
    
    public function removeResourcePermission($resourceActionId)
    {
        $role = Role::find($this->roleId);
        $role->resourceActions()->detach($resourceActionId);
        
        session()->flash('message', 'Resource permission removed!');
    }
    
    public function render()
    {
        $role = Role::with('resourceActions')->find($this->roleId);
        $availableResourceTypes = $this->getAvailableResourceTypes();
        
        return view('unperm::livewire.manage-role-resource-permissions', [
            'role' => $role,
            'availableResourceTypes' => $availableResourceTypes,
        ]);
    }
    
    protected function getAvailableResourceTypes()
    {
        // Получаем все модели с трейтом HasResourcePermissions
        // Можно использовать ModelDiscovery сервис
        return [
            'App\\Domain\\Folders\\Models\\Folder' => 'Folders',
            'App\\Domain\\Projects\\Models\\Project' => 'Projects',
            // и т.д.
        ];
    }
}
```

## Следующие шаги

1. ✅ Создать миграции
2. ✅ Добавить связи в модели
3. ⏳ Обновить HasPermissions trait для учета resourceActions из ролей
4. ⏳ Создать UI компонент для управления
5. ⏳ Добавить в существующие views
6. ⏳ Написать тесты

## Применить миграции

```bash
# В вашем основном приложении
php artisan migrate

# Или если в пакете
cd vendor/dfiks/unperm
php artisan migrate --path=database/migrations
```

