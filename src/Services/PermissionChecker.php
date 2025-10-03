<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Services;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Group;
use DFiks\UnPerm\Models\Role;
use Illuminate\Database\Eloquent\Model;

class PermissionChecker
{
    public function checkActionPermission(Action $action, int $requiredBit): bool
    {
        return $action->hasBit($requiredBit);
    }

    public function checkRolePermission(Role $role, int $requiredBit): bool
    {
        return $role->hasBit($requiredBit);
    }

    public function checkGroupPermission(Group $group, int $requiredBit): bool
    {
        return $group->hasBit($requiredBit);
    }

    public function checkRoleHasAction(Role $role, Action $action): bool
    {
        if ($role->hasAllBits($action->bitmask)) {
            return true;
        }

        return $role->actions()->where('actions.id', $action->id)->exists();
    }

    public function checkGroupHasAction(Group $group, Action $action): bool
    {
        if ($group->hasAllBits($action->bitmask)) {
            return true;
        }

        return $group->actions()->where('actions.id', $action->id)->exists();
    }

    public function checkGroupHasRole(Group $group, Role $role): bool
    {
        if ($group->hasAllBits($role->bitmask)) {
            return true;
        }

        return $group->roles()->where('roles.id', $role->id)->exists();
    }

    public function aggregateRoleBitmask(Role $role): int
    {
        $bitmask = $role->bitmask;

        foreach ($role->actions as $action) {
            $bitmask |= $action->bitmask;
        }

        return $bitmask;
    }

    public function aggregateGroupBitmask(Group $group): int
    {
        $bitmask = $group->bitmask;

        foreach ($group->roles as $role) {
            $bitmask |= $this->aggregateRoleBitmask($role);
        }

        foreach ($group->actions as $action) {
            $bitmask |= $action->bitmask;
        }

        return $bitmask;
    }

    public function modelCan(Model $model, Action|string $action): bool
    {
        if (!method_exists($model, 'hasAction')) {
            return false;
        }

        if ($model->hasAction($action)) {
            return true;
        }

        foreach ($model->roles as $role) {
            $actionModel = is_string($action)
                ? Action::where('slug', $action)->first()
                : $action;

            if ($actionModel && $this->checkRoleHasAction($role, $actionModel)) {
                return true;
            }
        }

        foreach ($model->groups as $group) {
            $actionModel = is_string($action)
                ? Action::where('slug', $action)->first()
                : $action;

            if ($actionModel && $this->checkGroupHasAction($group, $actionModel)) {
                return true;
            }
        }

        return false;
    }

    public function modelHasBit(Model $model, int $bit): bool
    {
        if (!method_exists($model, 'getPermissionBitmask')) {
            return false;
        }

        $bitmask = $model->getPermissionBitmask();

        return ($bitmask & (1 << $bit)) !== 0;
    }

    public function modelHasAllBits(Model $model, int $mask): bool
    {
        if (!method_exists($model, 'getPermissionBitmask')) {
            return false;
        }

        $bitmask = $model->getPermissionBitmask();

        return ($bitmask & $mask) === $mask;
    }

    public function modelHasAnyBit(Model $model, int $mask): bool
    {
        if (!method_exists($model, 'getPermissionBitmask')) {
            return false;
        }

        $bitmask = $model->getPermissionBitmask();

        return ($bitmask & $mask) !== 0;
    }
}
