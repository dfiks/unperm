<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Http\Livewire;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Role;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithPagination;

class ManageRoles extends Component
{
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $editingRoleId = null;
    public $name = '';
    public $slug = '';
    public $description = '';
    public $selectedActions = [];

    protected $queryString = ['search'];

    public function render()
    {
        $roles = Role::query()
            ->with('actions')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('slug', 'like', "%{$this->search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $actions = Action::orderBy('name')->get();

        return view('unperm::livewire.manage-roles', [
            'roles' => $roles,
            'actions' => $actions,
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
}

