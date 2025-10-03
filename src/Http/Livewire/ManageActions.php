<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Http\Livewire;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Support\PermBit;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithPagination;

class ManageActions extends Component
{
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $editingActionId = null;
    public $name = '';
    public $slug = '';
    public $description = '';

    protected $queryString = ['search'];

    public function render()
    {
        $actions = Action::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('slug', 'like', "%{$this->search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('unperm::livewire.manage-actions', [
            'actions' => $actions,
        ]);
    }

    public function create()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit($actionId)
    {
        $action = Action::findOrFail($actionId);
        $this->editingActionId = $action->id;
        $this->name = $action->name;
        $this->slug = $action->slug;
        $this->description = $action->description ?? '';
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
            'slug' => 'required|string|max:255|unique:actions,slug,' . $this->editingActionId,
            'description' => 'nullable|string',
        ])->validate();

        if ($this->editingActionId) {
            $action = Action::findOrFail($this->editingActionId);
            $action->update($validated);
            session()->flash('message', 'Action обновлен успешно!');
        } else {
            Action::create(array_merge($validated, ['bitmask' => '0']));
            PermBit::rebuild();
            session()->flash('message', 'Action создан успешно!');
        }

        $this->closeModal();
    }

    public function delete($actionId)
    {
        Action::findOrFail($actionId)->delete();
        session()->flash('message', 'Action удален успешно!');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    protected function resetForm()
    {
        $this->editingActionId = null;
        $this->name = '';
        $this->slug = '';
        $this->description = '';
    }
}

