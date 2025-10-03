<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Traits;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Group;
use DFiks\UnPerm\Models\Role;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasPermissions
{
    public function actions(): MorphToMany
    {
        return $this->morphToMany(
            Action::class,
            'model',
            'model_actions',
            'model_id',
            'action_id'
        )->withTimestamps();
    }

    public function roles(): MorphToMany
    {
        return $this->morphToMany(
            Role::class,
            'model',
            'model_roles',
            'model_id',
            'role_id'
        )->withTimestamps();
    }

    public function groups(): MorphToMany
    {
        return $this->morphToMany(
            Group::class,
            'model',
            'model_groups',
            'model_id',
            'group_id'
        )->withTimestamps();
    }

    public function assignAction(Action|string $action): self
    {
        if (is_string($action)) {
            $action = Action::where('slug', $action)->firstOrFail();
        }

        if (!$this->actions()->where('actions.id', $action->id)->exists()) {
            $this->actions()->attach($action->id);
        }

        return $this;
    }

    public function assignActions(array $actions): self
    {
        foreach ($actions as $action) {
            $this->assignAction($action);
        }

        return $this;
    }

    public function removeAction(Action|string $action): self
    {
        if (is_string($action)) {
            $action = Action::where('slug', $action)->firstOrFail();
        }

        $this->actions()->detach($action->id);

        return $this;
    }

    public function removeActions(array $actions): self
    {
        foreach ($actions as $action) {
            $this->removeAction($action);
        }

        return $this;
    }

    public function syncActions(array $actions): self
    {
        $actionIds = collect($actions)->map(function ($action) {
            if ($action instanceof Action) {
                return $action->id;
            }

            return Action::where('slug', $action)->firstOrFail()->id;
        })->toArray();

        $this->actions()->sync($actionIds);

        return $this;
    }

    public function assignRole(Role|string $role): self
    {
        if (is_string($role)) {
            $role = Role::where('slug', $role)->firstOrFail();
        }

        if (!$this->roles()->where('roles.id', $role->id)->exists()) {
            $this->roles()->attach($role->id);
        }

        return $this;
    }

    public function assignRoles(array $roles): self
    {
        foreach ($roles as $role) {
            $this->assignRole($role);
        }

        return $this;
    }

    public function removeRole(Role|string $role): self
    {
        if (is_string($role)) {
            $role = Role::where('slug', $role)->firstOrFail();
        }

        $this->roles()->detach($role->id);

        return $this;
    }

    public function removeRoles(array $roles): self
    {
        foreach ($roles as $role) {
            $this->removeRole($role);
        }

        return $this;
    }

    public function syncRoles(array $roles): self
    {
        $roleIds = collect($roles)->map(function ($role) {
            if ($role instanceof Role) {
                return $role->id;
            }

            return Role::where('slug', $role)->firstOrFail()->id;
        })->toArray();

        $this->roles()->sync($roleIds);

        return $this;
    }

    public function assignGroup(Group|string $group): self
    {
        if (is_string($group)) {
            $group = Group::where('slug', $group)->firstOrFail();
        }

        if (!$this->groups()->where('groups.id', $group->id)->exists()) {
            $this->groups()->attach($group->id);
        }

        return $this;
    }

    public function assignGroups(array $groups): self
    {
        foreach ($groups as $group) {
            $this->assignGroup($group);
        }

        return $this;
    }

    public function removeGroup(Group|string $group): self
    {
        if (is_string($group)) {
            $group = Group::where('slug', $group)->firstOrFail();
        }

        $this->groups()->detach($group->id);

        return $this;
    }

    public function removeGroups(array $groups): self
    {
        foreach ($groups as $group) {
            $this->removeGroup($group);
        }

        return $this;
    }

    public function syncGroups(array $groups): self
    {
        $groupIds = collect($groups)->map(function ($group) {
            if ($group instanceof Group) {
                return $group->id;
            }

            return Group::where('slug', $group)->firstOrFail()->id;
        })->toArray();

        $this->groups()->sync($groupIds);

        return $this;
    }

    public function hasAction(Action|string $action): bool
    {
        $slug = $action instanceof Action ? $action->slug : $action;
        
        $bitmask = $this->getPermissionBitmask();
        return \DFiks\UnPerm\Support\PermBit::hasAction($bitmask, $slug);
    }

    public function hasRole(Role|string $role): bool
    {
        if (is_string($role)) {
            return $this->roles()->where('slug', $role)->exists();
        }

        return $this->roles()->where('roles.id', $role->id)->exists();
    }

    public function hasGroup(Group|string $group): bool
    {
        if (is_string($group)) {
            return $this->groups()->where('slug', $group)->exists();
        }

        return $this->groups()->where('groups.id', $group->id)->exists();
    }

    public function hasAnyAction(array $actions): bool
    {
        $slugs = array_map(function ($action) {
            return $action instanceof Action ? $action->slug : $action;
        }, $actions);
        
        $bitmask = $this->getPermissionBitmask();
        return \DFiks\UnPerm\Support\PermBit::hasAnyAction($bitmask, $slugs);
    }

    public function hasAllActions(array $actions): bool
    {
        $slugs = array_map(function ($action) {
            return $action instanceof Action ? $action->slug : $action;
        }, $actions);
        
        $bitmask = $this->getPermissionBitmask();
        return \DFiks\UnPerm\Support\PermBit::hasAllActions($bitmask, $slugs);
    }

    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllRoles(array $roles): bool
    {
        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    public function hasAnyGroup(array $groups): bool
    {
        foreach ($groups as $group) {
            if ($this->hasGroup($group)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllGroups(array $groups): bool
    {
        foreach ($groups as $group) {
            if (!$this->hasGroup($group)) {
                return false;
            }
        }

        return true;
    }

    public function getPermissionBitmask(): string
    {
        $bitmask = gmp_init('0');

        foreach ($this->actions as $action) {
            $actionMask = gmp_init($action->bitmask);
            $bitmask = gmp_or($bitmask, $actionMask);
        }

        foreach ($this->roles as $role) {
            $roleMask = gmp_init($role->bitmask);
            $bitmask = gmp_or($bitmask, $roleMask);
        }

        foreach ($this->groups as $group) {
            $groupMask = gmp_init($group->bitmask);
            $bitmask = gmp_or($bitmask, $groupMask);
        }

        return gmp_strval($bitmask);
    }

    public function hasPermissionBit(int $bit): bool
    {
        $bitmask = gmp_init($this->getPermissionBitmask());
        $checkBit = gmp_pow(2, $bit);
        return gmp_cmp(gmp_and($bitmask, $checkBit), 0) !== 0;
    }

    public function hasPermissionBitmask(string|int $mask): bool
    {
        $bitmask = gmp_init($this->getPermissionBitmask());
        $checkMask = gmp_init((string)$mask);
        return gmp_cmp(gmp_and($bitmask, $checkMask), $checkMask) === 0;
    }

}
