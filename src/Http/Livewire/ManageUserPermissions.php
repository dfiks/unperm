<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Http\Livewire;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Group;
use DFiks\UnPerm\Models\Role;
use DFiks\UnPerm\Services\ModelDiscovery;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Livewire\WithPagination;

class ManageUserPermissions extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedUserModel = null;
    public $availableModels = [];
    public $selectedUserId = null;
    public $showPermissionsModal = false;
    
    public $userActions = [];
    public $userRoles = [];
    public $userGroups = [];
    
    public $availableActions = [];
    public $availableRoles = [];
    public $availableGroups = [];

    protected $queryString = ['search', 'selectedUserModel'];

    public function mount()
    {
        $discovery = new ModelDiscovery();
        $this->availableModels = $discovery->findModelsWithPermissions();
        
        // Выбираем модель по умолчанию
        if (empty($this->selectedUserModel) && !empty($this->availableModels)) {
            $defaultModel = $discovery->getDefaultUserModel();
            $this->selectedUserModel = $defaultModel ?? array_key_first($this->availableModels);
        }

        $this->loadAvailablePermissions();
    }

    public function render()
    {
        $users = collect();
        
        if ($this->selectedUserModel && class_exists($this->selectedUserModel)) {
            $query = $this->selectedUserModel::query();
            
            if ($this->search) {
                // Пробуем искать по разным полям
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                      ->orWhere('email', 'like', "%{$this->search}%");
                });
            }
            
            $users = $query->with(['actions', 'roles', 'groups'])
                          ->orderBy('created_at', 'desc')
                          ->paginate(15);
        }

        return view('unperm::livewire.manage-user-permissions', [
            'users' => $users,
        ]);
    }

    public function changeModel()
    {
        $this->resetPage();
        $this->search = '';
    }

    public function editPermissions($userId)
    {
        if (!$this->selectedUserModel || !class_exists($this->selectedUserModel)) {
            return;
        }

        try {
            // Безопасно находим пользователя
            $user = $this->selectedUserModel::with(['actions', 'roles', 'groups'])->find($userId);
            
            if (!$user) {
                session()->flash('error', 'Пользователь не найден');
                return;
            }

            $this->selectedUserId = $userId;
            
            // Загружаем текущие разрешения пользователя
            $this->userActions = $user->actions->pluck('id')->toArray();
            $this->userRoles = $user->roles->pluck('id')->toArray();
            $this->userGroups = $user->groups->pluck('id')->toArray();
            
            $this->showPermissionsModal = true;
        } catch (\Throwable $e) {
            session()->flash('error', 'Ошибка при загрузке пользователя: ' . $e->getMessage());
        }
    }

    public function savePermissions()
    {
        if (!$this->selectedUserModel || !$this->selectedUserId) {
            return;
        }

        try {
            $user = $this->selectedUserModel::find($this->selectedUserId);
            
            if (!$user) {
                session()->flash('error', 'Пользователь не найден');
                return;
            }
            
            // Синхронизируем разрешения
            $user->actions()->sync($this->userActions);
            $user->roles()->sync($this->userRoles);
            $user->groups()->sync($this->userGroups);

            session()->flash('message', 'Разрешения обновлены успешно!');
            $this->closeModal();
        } catch (\Throwable $e) {
            session()->flash('error', 'Ошибка при сохранении: ' . $e->getMessage());
        }
    }

    public function closeModal()
    {
        $this->showPermissionsModal = false;
        $this->selectedUserId = null;
        $this->userActions = [];
        $this->userRoles = [];
        $this->userGroups = [];
    }

    protected function loadAvailablePermissions()
    {
        $this->availableActions = Action::orderBy('name')->get();
        $this->availableRoles = Role::orderBy('name')->get();
        $this->availableGroups = Group::orderBy('name')->get();
    }

    public function getUserName($user): string
    {
        // Пробуем разные варианты получения имени
        return $user->name ?? $user->username ?? $user->email ?? "User #{$user->id}";
    }

    public function getUserIdentifier($user): string
    {
        return $user->email ?? $user->username ?? "ID: {$user->id}";
    }
}

