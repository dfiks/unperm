<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Services;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Group;
use DFiks\UnPerm\Models\Role;
use DFiks\UnPerm\Services\Concerns\AuthorizesServiceActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Schema;

class UserPermissionService
{
    use AuthorizesServiceActions;

    public function __construct(
        protected ModelDiscovery $modelDiscovery
    ) {
    }

    public function getUsers(string $modelClass, int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        $query = $modelClass::query();

        if ($search) {
            $query->where(function ($q) use ($search, $modelClass) {
                $instance = new $modelClass();
                $searchFields = ['name', 'email', 'username', 'title'];

                foreach ($searchFields as $field) {
                    if (Schema::hasColumn($instance->getTable(), $field)) {
                        $q->orWhere($field, 'like', "%{$search}%");
                    }
                }
            });
        }

        return $query->with(['actions', 'roles', 'groups', 'resourceActions'])->paginate($perPage);
    }

    public function getUser(string $modelClass, string $id): ?Model
    {
        return $modelClass::with(['actions', 'roles', 'groups', 'resourceActions'])->find($id);
    }

    public function assignAction(Model $user, Action $action): void
    {
        $this->authorize('admin.users.manage');

        $user->assignAction($action);
    }

    public function removeAction(Model $user, Action $action): void
    {
        $this->authorize('admin.users.manage');

        $user->removeAction($action);
    }

    public function assignRole(Model $user, Role $role): void
    {
        $this->authorize('admin.users.manage');

        $user->assignRole($role);
    }

    public function removeRole(Model $user, Role $role): void
    {
        $this->authorize('admin.users.manage');

        $user->removeRole($role);
    }

    public function assignGroup(Model $user, Group $group): void
    {
        $this->authorize('admin.users.manage');

        $user->assignGroup($group);
    }

    public function removeGroup(Model $user, Group $group): void
    {
        $this->authorize('admin.users.manage');

        $user->removeGroup($group);
    }

    public function syncActions(Model $user, array $actionIds): void
    {
        $this->authorize('admin.users.manage');

        $user->actions()->sync($actionIds);
    }

    public function syncRoles(Model $user, array $roleIds): void
    {
        $this->authorize('admin.users.manage');

        $user->roles()->sync($roleIds);
    }

    public function syncGroups(Model $user, array $groupIds): void
    {
        $this->authorize('admin.users.manage');

        $user->groups()->sync($groupIds);
    }

    public function bulkAssignRole(array $userIds, string $modelClass, Role $role): int
    {
        $this->authorize('admin.users.manage');

        $users = $modelClass::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            $user->assignRole($role);
        }

        return $users->count();
    }

    public function bulkAssignGroup(array $userIds, string $modelClass, Group $group): int
    {
        $this->authorize('admin.users.manage');

        $users = $modelClass::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            $user->assignGroup($group);
        }

        return $users->count();
    }

    public function getAllPermissions(Model $user): array
    {
        return [
            'direct_actions' => $user->actions,
            'roles' => $user->roles->load('actions'),
            'groups' => $user->groups->load(['actions', 'roles']),
            'resource_actions' => $user->resourceActions,
            'bitmask' => $user->getPermissionBitmask(),
            'is_superadmin' => method_exists($user, 'isSuperAdmin') ? $user->isSuperAdmin() : false,
        ];
    }

    public function getAvailableUserModels(): array
    {
        return $this->modelDiscovery->findModelsWithPermissions();
    }
}
