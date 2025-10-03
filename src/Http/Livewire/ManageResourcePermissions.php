<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Http\Livewire;

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
    public array $availableActions = ['view', 'edit', 'delete', 'create', 'update', 'archive'];
    public array $userPermissions = [];
    
    public string $search = '';
    public string $newUserEmail = '';
    public array $newUserActions = [];

    protected $queryString = ['selectedResourceModel' => ['except' => '']];

    public function mount(ModelDiscovery $modelDiscovery)
    {
        $this->availableResourceModels = $modelDiscovery->findModelsWithResourcePermissions();
        $this->availableUserModels = $modelDiscovery->findModelsWithPermissions();
        
        if (empty($this->selectedResourceModel) && !empty($this->availableResourceModels)) {
            $this->selectedResourceModel = array_key_first($this->availableResourceModels);
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
            
            // Загружаем текущие разрешения
            $this->loadCurrentPermissions($resource);
            
            $this->showPermissionsModal = true;
        } catch (\Throwable $e) {
            session()->flash('error', 'Ошибка при загрузке ресурса: ' . $e->getMessage());
        }
    }

    protected function loadCurrentPermissions($resource)
    {
        $this->currentPermissions = [];
        $this->userPermissions = [];
        
        $resourceKey = $resource->getResourcePermissionKey();
        $resourceId = $resource->getResourcePermissionId();
        
        // Получаем всех пользователей с правами на этот ресурс
        foreach ($this->availableActions as $action) {
            $users = ResourcePermission::getUsersWithAccess($resource, $action);
            
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
                
                $this->userPermissions[$userId]['actions'][] = $action;
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
            'newUserEmail' => 'required|email',
            'newUserActions' => 'required|array|min:1',
        ], [
            'newUserEmail.required' => 'Укажите email пользователя',
            'newUserEmail.email' => 'Неверный формат email',
            'newUserActions.required' => 'Выберите хотя бы одно действие',
        ]);
        
        try {
            // Ищем пользователя по email во всех user моделях
            $user = null;
            foreach ($this->availableUserModels as $userModelClass => $info) {
                $found = $userModelClass::where('email', $this->newUserEmail)->first();
                if ($found) {
                    $user = $found;
                    break;
                }
            }
            
            if (!$user) {
                session()->flash('error', 'Пользователь с таким email не найден');
                return;
            }
            
            $resource = $this->selectedResourceModel::whereKey($this->selectedResourceId)->first();
            
            if (!$resource) {
                session()->flash('error', 'Ресурс не найден');
                return;
            }
            
            // Назначаем права
            foreach ($this->newUserActions as $action) {
                ResourcePermission::grant($user, $resource, $action);
            }
            
            // Обновляем список
            $this->loadCurrentPermissions($resource);
            
            // Очищаем форму
            $this->newUserEmail = '';
            $this->newUserActions = [];
            
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
        $this->newUserEmail = '';
        $this->newUserActions = [];
    }
}

