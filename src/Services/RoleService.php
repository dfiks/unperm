<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Services;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\ResourceAction;
use DFiks\UnPerm\Models\Role;
use DFiks\UnPerm\Services\Concerns\AuthorizesServiceActions;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class RoleService
{
    use AuthorizesServiceActions;

    public function getAll(): Collection
    {
        $this->authorize('admin.permissions.view');

        return Role::with(['actions', 'resourceActions'])->orderBy('name')->get();
    }

    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        $this->authorize('admin.permissions.view');

        $query = Role::with(['actions', 'resourceActions']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('slug', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function find(string $id): ?Role
    {
        $this->authorize('admin.permissions.view');

        return Role::with(['actions', 'resourceActions', 'groups'])->find($id);
    }

    public function findBySlug(string $slug): ?Role
    {
        $this->authorize('admin.permissions.view');

        return Role::where('slug', $slug)->first();
    }

    public function create(array $data): Role
    {
        $this->authorize('admin.permissions.manage');

        $role = Role::create([
            'slug' => $data['slug'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        if (!empty($data['action_ids'])) {
            $role->actions()->sync($data['action_ids']);
        }

        return $role->fresh(['actions', 'resourceActions']);
    }

    public function update(Role $role, array $data): Role
    {
        $this->authorize('admin.permissions.manage');

        $role->update([
            'slug' => $data['slug'] ?? $role->slug,
            'name' => $data['name'] ?? $role->name,
            'description' => $data['description'] ?? $role->description,
        ]);

        if (isset($data['action_ids'])) {
            $role->actions()->sync($data['action_ids']);
        }

        return $role->fresh(['actions', 'resourceActions']);
    }

    public function delete(Role $role): bool
    {
        $this->authorize('admin.permissions.manage');

        return $role->delete();
    }

    public function attachAction(Role $role, Action $action): void
    {
        $this->authorize('admin.permissions.manage');

        $role->actions()->syncWithoutDetaching([$action->id]);
    }

    public function detachAction(Role $role, Action $action): void
    {
        $this->authorize('admin.permissions.manage');

        $role->actions()->detach($action->id);
    }

    public function syncActions(Role $role, array $actionIds): void
    {
        $this->authorize('admin.permissions.manage');

        $role->actions()->sync($actionIds);
    }

    public function attachResourceAction(Role $role, ResourceAction $resourceAction): void
    {
        $this->authorize('admin.permissions.manage');

        $role->resourceActions()->syncWithoutDetaching([$resourceAction->id]);
    }

    public function detachResourceAction(Role $role, ResourceAction $resourceAction): void
    {
        $this->authorize('admin.permissions.manage');

        $role->resourceActions()->detach($resourceAction->id);
    }

    public function getUsersCount(Role $role): int
    {
        $this->authorize('admin.permissions.view');

        $count = 0;

        $modelDiscovery = app(ModelDiscovery::class);
        $userModels = $modelDiscovery->findModelsWithPermissions();

        foreach ($userModels as $modelClass) {
            $count += $modelClass['class']::whereHas('roles', function ($q) use ($role) {
                $q->where('roles.id', $role->id);
            })->count();
        }

        return $count;
    }

    public function sync(array $roles): void
    {
        if (!$this->isSuperAdmin()) {
            $this->authorize('admin.permissions.manage');
        }

        foreach ($roles as $slug => $roleData) {
            $role = Role::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $roleData['name'] ?? ucfirst($slug),
                    'description' => $roleData['description'] ?? null,
                ]
            );

            if (!empty($roleData['actions'])) {
                $actionIds = Action::whereIn('slug', $roleData['actions'])->pluck('id')->toArray();
                $role->actions()->sync($actionIds);
            }
        }
    }
}
