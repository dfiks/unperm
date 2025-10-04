<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Services;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Services\Concerns\AuthorizesServiceActions;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ActionService
{
    use AuthorizesServiceActions;

    public function getAll(): Collection
    {
        $this->authorize('admin.permissions.view');

        return Action::orderBy('slug')->get();
    }

    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        $this->authorize('admin.permissions.view');

        $query = Action::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('slug')->paginate($perPage);
    }

    public function find(string $id): ?Action
    {
        $this->authorize('admin.permissions.view');

        return Action::find($id);
    }

    public function findBySlug(string $slug): ?Action
    {
        $this->authorize('admin.permissions.view');

        return Action::where('slug', $slug)->first();
    }

    public function create(array $data): Action
    {
        $this->authorize('admin.permissions.manage');

        return Action::create([
            'name' => $data['name'] ?? $data['slug'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
        ]);
    }

    public function update(Action $action, array $data): Action
    {
        $this->authorize('admin.permissions.manage');

        $action->update([
            'name' => $data['name'] ?? $action->name,
            'slug' => $data['slug'] ?? $action->slug,
            'description' => $data['description'] ?? $action->description,
        ]);

        return $action->fresh();
    }

    public function delete(Action $action): bool
    {
        $this->authorize('admin.permissions.manage');

        return $action->delete();
    }

    public function sync(array $actions): void
    {
        // Синхронизация требует прав супер-админа
        if (!$this->isSuperAdmin()) {
            $this->authorize('admin.permissions.manage');
        }

        foreach ($actions as $slug => $description) {
            Action::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => is_string($description) && $description ? $description : $slug,
                    'description' => is_string($description) ? $description : null,
                ]
            );
        }
    }

    public function getUsersCount(Action $action): int
    {
        $this->authorize('admin.permissions.view');

        return $action->users()->count();
    }

    public function getRolesCount(Action $action): int
    {
        $this->authorize('admin.permissions.view');

        return $action->roles()->count();
    }

    public function getGroupsCount(Action $action): int
    {
        $this->authorize('admin.permissions.view');

        return $action->groups()->count();
    }
}
