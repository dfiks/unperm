<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Http\Livewire;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\ResourceAction;
use DFiks\UnPerm\Support\PermBit;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithPagination;
use Exception;

class ManageActions extends Component
{
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $editingActionId = null;
    public $name = '';
    public $slug = '';
    public $description = '';
    public array $expandedActions = [];

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

        // Загружаем resource actions для раскрытых actions
        $resourceActionsMap = [];
        if (!empty($this->expandedActions)) {
            foreach ($this->expandedActions as $actionId => $isExpanded) {
                if ($isExpanded) {
                    $action = $actions->firstWhere('id', $actionId);
                    if ($action) {
                        // Ищем ResourceAction, у которых slug начинается с префикса этого action
                        // Например, для action "folders.view" ищем "folders.view.{uuid}"
                        $resourceActions = ResourceAction::where('slug', 'like', $action->slug . '.%')
                            ->orderBy('created_at', 'desc')
                            ->limit(20)
                            ->get();

                        // Добавляем количество пользователей для каждого action
                        $resourceActions->each(function ($resourceAction) {
                            $resourceAction->usersCount = $resourceAction->getUsersCount();
                        });

                        $resourceActionsMap[$actionId] = $resourceActions;
                    }
                }
            }
        }

        // Также загружаем Resource Actions, для которых НЕТ глобального Action
        // Группируем по resource_type и action_type
        $orphanedResourceActions = ResourceAction::selectRaw('resource_type, action_type, COUNT(*) as count, MAX(created_at) as latest')
            ->groupBy('resource_type', 'action_type')
            ->havingRaw('COUNT(*) > 0')
            ->get()
            ->filter(function ($group) use ($actions) {
                // Проверяем, есть ли глобальный action для этой группы
                $expectedSlug = $this->getResourceKeyFromType($group->resource_type) . '.' . $group->action_type;

                return !$actions->contains('slug', $expectedSlug);
            });

        return view('unperm::livewire.manage-actions', [
            'actions' => $actions,
            'resourceActionsMap' => $resourceActionsMap,
            'orphanedResourceActions' => $orphanedResourceActions,
        ]);
    }

    protected function getResourceKeyFromType(string $resourceType): string
    {
        // Пытаемся получить resource key из типа модели
        if (class_exists($resourceType)) {
            $model = new $resourceType();
            if (method_exists($model, 'getResourcePermissionKey')) {
                return $model->getResourcePermissionKey();
            }
            if (method_exists($model, 'getTable')) {
                return $model->getTable();
            }
        }

        return class_basename($resourceType);
    }

    public function createGlobalActionFromGroup($resourceType, $actionType)
    {
        try {
            $resourceKey = $this->getResourceKeyFromType($resourceType);
            $slug = $resourceKey . '.' . $actionType;

            // Проверяем что такого action еще нет
            if (Action::where('slug', $slug)->exists()) {
                session()->flash('error', 'Global action уже существует: ' . $slug);

                return;
            }

            // Создаем глобальный action
            $name = ucfirst($actionType) . ' ' . ucfirst($resourceKey);
            $description = ucfirst($actionType) . ' permission for ' . $resourceKey;

            Action::create([
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'bitmask' => '0',
            ]);

            PermBit::rebuild();

            session()->flash('message', 'Global action создан: ' . $slug);
        } catch (Exception $e) {
            session()->flash('error', 'Ошибка создания action: ' . $e->getMessage());
        }
    }

    public function toggleExpand($actionId)
    {
        if (isset($this->expandedActions[$actionId])) {
            $this->expandedActions[$actionId] = !$this->expandedActions[$actionId];
        } else {
            $this->expandedActions[$actionId] = true;
        }
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
