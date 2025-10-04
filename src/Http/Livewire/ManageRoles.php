<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Http\Livewire;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\ResourceAction;
use DFiks\UnPerm\Models\Role;
use DFiks\UnPerm\Services\ModelDiscovery;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithPagination;

class ManageRoles extends Component
{
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $showResourceModal = false;
    public $editingRoleId = null;
    public $managingResourcesForRoleId = null;
    public $name = '';
    public $slug = '';
    public $description = '';
    public $selectedActions = [];
    
    // Для ResourceActions
    public $selectedResourceType = '';
    public $selectedResourceId = '';
    public $selectedResourceAction = 'view';
    public $availableResources = [];
    public $resourceSearch = '';

    protected $queryString = ['search'];

    public function render()
    {
        $roles = Role::query()
            ->with(['actions', 'resourceActions'])
            ->when($this->search, function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('slug', 'like', "%{$this->search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $allActions = Action::orderBy('name')->get();
        
        // Для модального окна ResourceActions
        $availableResourceTypes = [];
        if ($this->showResourceModal) {
            $availableResourceTypes = $this->getAvailableResourceTypes();
        }

        return view('unperm::livewire.manage-roles', [
            'roles' => $roles,
            'allActions' => $allActions,
            'availableResourceTypes' => $availableResourceTypes,
        ]);
    }

    public function create()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit($roleId)
    {
        $role = Role::with('actions')->findOrFail($roleId);
        $this->editingRoleId = $role->id;
        $this->name = $role->name;
        $this->slug = $role->slug;
        $this->description = $role->description ?? '';
        $this->selectedActions = $role->actions->pluck('id')->toArray();
        $this->showModal = true;
    }

    public function save()
    {
        $validated = Validator::make([
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
        ], [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug,' . $this->editingRoleId,
            'description' => 'nullable|string',
        ])->validate();

        if ($this->editingRoleId) {
            $role = Role::findOrFail($this->editingRoleId);
            $role->update($validated);
        } else {
            $role = Role::create(array_merge($validated, ['bitmask' => '0']));
        }

        // Синхронизируем actions
        $role->actions()->sync($this->selectedActions);
        $role->syncBitmaskFromActions();

        session()->flash('message', $this->editingRoleId ? 'Роль обновлена успешно!' : 'Роль создана успешно!');
        $this->closeModal();
    }

    public function delete($roleId)
    {
        Role::findOrFail($roleId)->delete();
        session()->flash('message', 'Роль удалена успешно!');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    protected function resetForm()
    {
        $this->editingRoleId = null;
        $this->name = '';
        $this->slug = '';
        $this->description = '';
        $this->selectedActions = [];
    }
    
    // ResourceActions Management
    
    public function manageResources($roleId)
    {
        $this->managingResourcesForRoleId = $roleId;
        $this->showResourceModal = true;
        $this->resetResourceForm();
    }
    
    public function updatedSelectedResourceType()
    {
        $this->loadAvailableResources();
    }
    
    public function updatedResourceSearch()
    {
        $this->loadAvailableResources();
    }
    
    protected function loadAvailableResources()
    {
        if (!$this->selectedResourceType || !class_exists($this->selectedResourceType)) {
            $this->availableResources = [];
            return;
        }
        
        $query = $this->selectedResourceType::query();
        
        // Добавляем поиск
        if ($this->resourceSearch) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->resourceSearch}%")
                    ->orWhere('title', 'like', "%{$this->resourceSearch}%");
            });
        }
        
        $this->availableResources = $query
            ->select('id', 'name')
            ->limit(50)
            ->get()
            ->map(function ($resource) {
                return [
                    'id' => $resource->id,
                    'name' => $resource->name ?? $resource->title ?? $resource->id,
                ];
            })
            ->toArray();
    }
    
    public function addResourcePermission()
    {
        $this->validate([
            'selectedResourceType' => 'required',
            'selectedResourceId' => 'required',
            'selectedResourceAction' => 'required',
        ]);
        
        try {
            $role = Role::findOrFail($this->managingResourcesForRoleId);
            $resource = $this->selectedResourceType::findOrFail($this->selectedResourceId);
            
            // Создаем или получаем ResourceAction
            $resourceAction = ResourceAction::findOrCreateForResource($resource, $this->selectedResourceAction);
            
            // Назначаем роли
            if (!$role->resourceActions()->where('resource_action_id', $resourceAction->id)->exists()) {
                $role->resourceActions()->attach($resourceAction->id);
                session()->flash('message', 'Resource permission добавлено!');
            } else {
                session()->flash('error', 'Это право уже назначено роли');
            }
            
            $this->resetResourceForm();
        } catch (\Exception $e) {
            session()->flash('error', 'Ошибка: ' . $e->getMessage());
        }
    }
    
    public function removeResourcePermission($roleId, $resourceActionId)
    {
        try {
            $role = Role::findOrFail($roleId);
            $role->resourceActions()->detach($resourceActionId);
            session()->flash('message', 'Resource permission удалено!');
        } catch (\Exception $e) {
            session()->flash('error', 'Ошибка: ' . $e->getMessage());
        }
    }
    
    public function closeResourceModal()
    {
        $this->showResourceModal = false;
        $this->resetResourceForm();
    }
    
    protected function resetResourceForm()
    {
        $this->selectedResourceType = '';
        $this->selectedResourceId = '';
        $this->selectedResourceAction = 'view';
        $this->availableResources = [];
        $this->resourceSearch = '';
    }
    
    protected function getAvailableResourceTypes(): array
    {
        $modelDiscovery = app(ModelDiscovery::class);
        $models = $modelDiscovery->findModelsWithResourcePermissions();
        
        $types = [];
        foreach ($models as $class => $info) {
            $types[$class] = $info['name'];
        }
        
        return $types;
    }
}
