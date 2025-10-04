<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Services;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Group;
use DFiks\UnPerm\Models\ResourceAction;
use DFiks\UnPerm\Models\Role;
use DFiks\UnPerm\Services\Concerns\AuthorizesServiceActions;
use Illuminate\Pagination\LengthAwarePaginator;

class GroupService
{
    use AuthorizesServiceActions;

    public function getAll()
    {
        $this->authorize('admin.permissions.view');

        return Group::with(['actions', 'roles', 'resourceActions'])->orderBy('name')->get();
    }

    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        $this->authorize('admin.permissions.view');

        $query = Group::with(['actions', 'roles', 'resourceActions']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('slug', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function find(string $id): ?Group
    {
        return Group::with(['actions', 'roles', 'resourceActions'])->find($id);
    }

    public function findBySlug(string $slug): ?Group
    {
        return Group::where('slug', $slug)->first();
    }

    public function create(array $data): Group
    {
        $this->authorize('admin.permissions.manage');

        $group = Group::create([
            'slug' => $data['slug'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        if (!empty($data['action_ids'])) {
            $group->actions()->sync($data['action_ids']);
        }

        if (!empty($data['role_ids'])) {
            $group->roles()->sync($data['role_ids']);
        }

        return $group->fresh(['actions', 'roles', 'resourceActions']);
    }

    public function update(Group $group, array $data): Group
    {
        $this->authorize('admin.permissions.manage');

        $group->update([
            'slug' => $data['slug'] ?? $group->slug,
            'name' => $data['name'] ?? $group->name,
            'description' => $data['description'] ?? $group->description,
        ]);

        if (isset($data['action_ids'])) {
            $group->actions()->sync($data['action_ids']);
        }

        if (isset($data['role_ids'])) {
            $group->roles()->sync($data['role_ids']);
        }

        return $group->fresh(['actions', 'roles', 'resourceActions']);
    }

    public function delete(Group $group): bool
    {
        $this->authorize('admin.permissions.manage');

        return $group->delete();
    }

    public function attachAction(Group $group, Action $action): void
    {
        $group->actions()->syncWithoutDetaching([$action->id]);
    }

    public function detachAction(Group $group, Action $action): void
    {
        $group->actions()->detach($action->id);
    }

    public function syncActions(Group $group, array $actionIds): void
    {
        $group->actions()->sync($actionIds);
    }

    public function attachRole(Group $group, Role $role): void
    {
        $group->roles()->syncWithoutDetaching([$role->id]);
    }

    public function detachRole(Group $group, Role $role): void
    {
        $group->roles()->detach($role->id);
    }

    public function syncRoles(Group $group, array $roleIds): void
    {
        $group->roles()->sync($roleIds);
    }

    public function attachResourceAction(Group $group, ResourceAction $resourceAction): void
    {
        $group->resourceActions()->syncWithoutDetaching([$resourceAction->id]);
    }

    public function detachResourceAction(Group $group, ResourceAction $resourceAction): void
    {
        $group->resourceActions()->detach($resourceAction->id);
    }

    public function getUsersCount(Group $group): int
    {
        $count = 0;

        $modelDiscovery = app(ModelDiscovery::class);
        $userModels = $modelDiscovery->findModelsWithPermissions();

        foreach ($userModels as $modelClass) {
            $count += $modelClass::whereHas('groups', function ($q) use ($group) {
                $q->where('groups.id', $group->id);
            })->count();
        }

        return $count;
    }

    public function sync(array $groups): void
    {
        foreach ($groups as $slug => $groupData) {
            $group = Group::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $groupData['name'] ?? ucfirst($slug),
                    'description' => $groupData['description'] ?? null,
                ]
            );

            if (!empty($groupData['actions'])) {
                $actionIds = Action::whereIn('slug', $groupData['actions'])->pluck('id')->toArray();
                $group->actions()->sync($actionIds);
            }

            if (!empty($groupData['roles'])) {
                $roleIds = Role::whereIn('slug', $groupData['roles'])->pluck('id')->toArray();
                $group->roles()->sync($roleIds);
            }
        }
    }
}
