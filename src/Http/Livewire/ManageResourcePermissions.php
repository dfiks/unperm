<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Http\Livewire;

use DFiks\UnPerm\Models\ResourceAction;
use DFiks\UnPerm\Services\ModelDiscovery;
use DFiks\UnPerm\Support\ResourcePermission;
use Livewire\Component;
use Livewire\WithPagination;

class ManageResourcePermissions extends Component
{
    use WithPagination;

    public array $availableResourceModels = [];
    public array $availableUserModels = [];
    
    public ?string $selectedResourceModel = null;
    public ?string $selectedResourceId = null;
    public ?string $resourceName = null;
    
    public bool $showPermissionsModal = false;
    
    public array $currentPermissions = [];
    public array $availableActions = ['view', 'create', 'update', 'delete'];
    public array $userPermissions = [];
    public array $availableUsers = [];
    
    public string $search = '';
    public ?string $newUserId = null;
    public ?string $selectedUserModel = null;
    public array $newUserActions = [];
    public string $customAction = '';

    protected $queryString = ['selectedResourceModel' => ['except' => '']];

    public function mount(ModelDiscovery $modelDiscovery)
    {
        $this->availableResourceModels = $modelDiscovery->findModelsWithResourcePermissions();
        $this->availableUserModels = $modelDiscovery->findModelsWithPermissions();
        
        if (empty($this->selectedResourceModel) && !empty($this->availableResourceModels)) {
            $this->selectedResourceModel = array_key_first($this->availableResourceModels);
        }
        
        if (empty($this->selectedUserModel) && !empty($this->availableUserModels)) {
            $this->selectedUserModel = array_key_first($this->availableUserModels);
        }
        
        $this->loadAvailableUsers();
    }
    
    protected function loadAvailableUsers()
    {
        if (!$this->selectedUserModel || !class_exists($this->selectedUserModel)) {
            $this->availableUsers = [];
            return;
        }
        
        try {
            $query = $this->selectedUserModel::query();
            
            // Пытаемся сортировать по name, если поле существует
            if (method_exists($this->selectedUserModel, 'getConnection')) {
                $instance = new $this->selectedUserModel;
                if ($instance->getConnection()->getSchemaBuilder()->hasColumn($instance->getTable(), 'name')) {
                    $query->orderBy('name');
                } elseif ($instance->getConnection()->getSchemaBuilder()->hasColumn($instance->getTable(), 'email')) {
                    $query->orderBy('email');
                }
            }
            
            $users = $query->get();
            $this->availableUsers = $users->mapWithKeys(function ($user) {
                $name = $user->name ?? $user->email ?? 'User #' . $user->getKey();
                $label = $name;
                
                // Добавляем email если есть и это не имя
                if (isset($user->email) && $user->email !== $name) {
                    $label .= ' (' . $user->email . ')';
                }
                
                return [$user->getKey() => $label];
            })->toArray();
        } catch (\Throwable $e) {
            $this->availableUsers = [];
        }
    }

    public function render()
    {
        $resources = collect();
        
        if ($this->selectedResourceModel && class_exists($this->selectedResourceModel)) {
            try {
                $query = $this->selectedResourceModel::query();
                
                if ($this->search) {
                    $query->where(function ($q) {
                        $searchFields = ['name', 'title', 'slug', 'description'];
                        foreach ($searchFields as $field) {
                            $q->orWhere($field, 'like', "%{$this->search}%");
                        }
                    });
                }
                
                $resources = $query->orderBy('created_at', 'desc')->paginate(15);
            } catch (\Throwable $e) {
                session()->flash('error', 'Ошибка при загрузке ресурсов: ' . $e->getMessage());
                $resources = collect();
            }
        }
        
        return view('unperm::livewire.manage-resource-permissions', [
            'resources' => $resources,
        ]);
    }

    public function changeModel()
    {
        $this->resetPage();
        $this->search = '';
    }

    public function managePermissions($resourceId)
    {
        if (!$this->selectedResourceModel || !class_exists($this->selectedResourceModel)) {
            return;
        }
        
        if (empty($resourceId)) {
            session()->flash('error', 'ID ресурса не указан');
            return;
        }
        
        try {
            $resource = $this->selectedResourceModel::whereKey($resourceId)->first();
            
            if (!$resource) {
                session()->flash('error', "Ресурс с ID '{$resourceId}' не найден");
                return;
            }
            
            $this->selectedResourceId = $resourceId;
            $this->resourceName = $this->getResourceDisplayName($resource);
            
            // Загружаем текущие разрешения и пользователей
            $this->loadCurrentPermissions($resource);
            $this->loadAvailableUsers();
            
            $this->showPermissionsModal = true;
        } catch (\Throwable $e) {
            session()->flash('error', 'Ошибка при загрузке ресурса: ' . $e->getMessage());
        }
    }

    protected function loadCurrentPermissions($resource)
    {
        $this->currentPermissions = [];
        $this->userPermissions = [];
        
        // Получаем все resource actions для этого ресурса
        $resourceActions = ResourceAction::getForResource($resource);
        
        // Для каждого resource action получаем пользователей
        foreach ($resourceActions as $resourceAction) {
            foreach ($this->availableUserModels as $userModelClass => $info) {
                try {
                    $users = $resourceAction->users()->where('model_type', $userModelClass)->get();
                    
                    foreach ($users as $user) {
                        $userId = $user->getKey();
                        
                        if (!isset($this->userPermissions[$userId])) {
                            $this->userPermissions[$userId] = [
                                'id' => $userId,
                                'name' => $user->name ?? $user->email ?? "User {$userId}",
                                'email' => $user->email ?? '',
                                'actions' => [],
                            ];
                        }
                        
                        if (!in_array($resourceAction->action_type, $this->userPermissions[$userId]['actions'])) {
                            $this->userPermissions[$userId]['actions'][] = $resourceAction->action_type;
                        }
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }
        }
    }

    protected function getResourceDisplayName($resource): string
    {
        if (isset($resource->name)) {
            return $resource->name;
        }
        
        if (isset($resource->title)) {
            return $resource->title;
        }
        
        if (isset($resource->slug)) {
            return $resource->slug;
        }
        
        return class_basename($resource) . ' #' . $resource->getKey();
    }

    public function addUserPermission()
    {
        $this->validate([
            'newUserId' => 'required',
            'newUserActions' => 'required|array|min:1',
        ], [
            'newUserId.required' => 'Выберите пользователя',
            'newUserActions.required' => 'Выберите хотя бы одно действие',
        ]);
        
        try {
            // Находим пользователя по ID
            if (!$this->selectedUserModel || !class_exists($this->selectedUserModel)) {
                session()->flash('error', 'Модель пользователя не выбрана');
                return;
            }
            
            $user = $this->selectedUserModel::whereKey($this->newUserId)->first();
            
            if (!$user) {
                session()->flash('error', 'Пользователь не найден');
                return;
            }
            
            $resource = $this->selectedResourceModel::whereKey($this->selectedResourceId)->first();
            
            if (!$resource) {
                session()->flash('error', 'Ресурс не найден');
                return;
            }
            
            // Собираем все действия (включая кастомное)
            $actionsToGrant = $this->newUserActions;
            
            // Добавляем кастомное действие, если указано
            if (!empty($this->customAction)) {
                $actionsToGrant[] = trim($this->customAction);
            }
            
            // Назначаем права
            foreach ($actionsToGrant as $action) {
                if (!empty($action)) {
                    ResourcePermission::grant($user, $resource, $action);
                }
            }
            
            // Обновляем список
            $this->loadCurrentPermissions($resource);
            
            // Очищаем форму
            $this->newUserId = null;
            $this->newUserActions = [];
            $this->customAction = '';
            
            session()->flash('message', 'Права успешно назначены');
        } catch (\Throwable $e) {
            session()->flash('error', 'Ошибка: ' . $e->getMessage());
        }
    }

    public function revokeUserPermission($userId, $action)
    {
        try {
            // Находим пользователя
            $user = null;
            foreach ($this->availableUserModels as $userModelClass => $info) {
                $found = $userModelClass::whereKey($userId)->first();
                if ($found) {
                    $user = $found;
                    break;
                }
            }
            
            if (!$user) {
                session()->flash('error', 'Пользователь не найден');
                return;
            }
            
            $resource = $this->selectedResourceModel::whereKey($this->selectedResourceId)->first();
            
            if (!$resource) {
                session()->flash('error', 'Ресурс не найден');
                return;
            }
            
            ResourcePermission::revoke($user, $resource, $action);
            
            // Обновляем список
            $this->loadCurrentPermissions($resource);
            
            session()->flash('message', 'Право отозвано');
        } catch (\Throwable $e) {
            session()->flash('error', 'Ошибка: ' . $e->getMessage());
        }
    }

    public function revokeAllUserPermissions($userId)
    {
        try {
            $user = null;
            foreach ($this->availableUserModels as $userModelClass => $info) {
                $found = $userModelClass::whereKey($userId)->first();
                if ($found) {
                    $user = $found;
                    break;
                }
            }
            
            if (!$user) {
                session()->flash('error', 'Пользователь не найден');
                return;
            }
            
            $resource = $this->selectedResourceModel::whereKey($this->selectedResourceId)->first();
            
            if (!$resource) {
                session()->flash('error', 'Ресурс не найден');
                return;
            }
            
            ResourcePermission::revokeAll($user, $resource);
            
            // Обновляем список
            $this->loadCurrentPermissions($resource);
            
            session()->flash('message', 'Все права пользователя отозваны');
        } catch (\Throwable $e) {
            session()->flash('error', 'Ошибка: ' . $e->getMessage());
        }
    }

    public function closeModal()
    {
        $this->showPermissionsModal = false;
        $this->selectedResourceId = null;
        $this->resourceName = null;
        $this->currentPermissions = [];
        $this->userPermissions = [];
        $this->newUserId = null;
        $this->newUserActions = [];
        $this->customAction = '';
    }
    
    public function updatedSelectedUserModel()
    {
        $this->loadAvailableUsers();
        $this->newUserId = null;
    }
}

