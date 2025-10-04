<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Http\Livewire;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Group;
use DFiks\UnPerm\Models\Role;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithPagination;

class ManageGroups extends Component
{
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $editingGroupId = null;
    public $name = '';
    public $slug = '';
    public $description = '';
    public $selectedActions = [];
    public $selectedRoles = [];

    protected $queryString = ['search'];

    public function render()
    {
        $groups = Group::query()
            ->with(['actions', 'roles'])
            ->when($this->search, function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('slug', 'like', "%{$this->search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $allActions = Action::orderBy('name')->get();
        $allRoles = Role::orderBy('name')->get();

        return view('unperm::livewire.manage-groups', [
            'groups' => $groups,
            'allActions' => $allActions,
            'allRoles' => $allRoles,
        ]);
    }

    public function create()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit($groupId)
    {
        $group = Group::with(['actions', 'roles'])->findOrFail($groupId);
        $this->editingGroupId = $group->id;
        $this->name = $group->name;
        $this->slug = $group->slug;
        $this->description = $group->description ?? '';
        $this->selectedActions = $group->actions->pluck('id')->toArray();
        $this->selectedRoles = $group->roles->pluck('id')->toArray();
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
            'slug' => 'required|string|max:255|unique:groups,slug,' . $this->editingGroupId,
            'description' => 'nullable|string',
        ])->validate();

        if ($this->editingGroupId) {
            $group = Group::findOrFail($this->editingGroupId);
            $group->update($validated);
        } else {
            $group = Group::create(array_merge($validated, ['bitmask' => '0']));
        }

        // Синхронизируем actions и roles
        $group->actions()->sync($this->selectedActions);
        $group->roles()->sync($this->selectedRoles);
        $group->syncBitmaskFromRolesAndActions();

        session()->flash('message', $this->editingGroupId ? 'Группа обновлена успешно!' : 'Группа создана успешно!');
        $this->closeModal();
    }

    public function delete($groupId)
    {
        Group::findOrFail($groupId)->delete();
        session()->flash('message', 'Группа удалена успешно!');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    protected function resetForm()
    {
        $this->editingGroupId = null;
        $this->name = '';
        $this->slug = '';
        $this->description = '';
        $this->selectedActions = [];
        $this->selectedRoles = [];
    }
}
