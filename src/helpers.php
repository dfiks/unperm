<?php

use Illuminate\Database\Eloquent\Model;

if (!function_exists('canPermission')) {
    /**
     * Проверить право доступа.
     */
    function canPermission(string $ability, mixed $arguments = null, ?Model $user = null): bool
    {
        return app('unperm.gate')->check($ability, $arguments, $user);
    }
}

if (!function_exists('authorizePermission')) {
    /**
     * Убедиться что есть право доступа (или throw exception).
     */
    function authorizePermission(string $ability, mixed $arguments = null, ?Model $user = null): void
    {
        app('unperm.gate')->authorize($ability, $arguments, $user);
    }
}

if (!function_exists('canAnyPermission')) {
    /**
     * Проверить любое из прав.
     */
    function canAnyPermission(array $abilities, mixed $arguments = null, ?Model $user = null): bool
    {
        return app('unperm.gate')->any($abilities, $arguments, $user);
    }
}

if (!function_exists('canAllPermissions')) {
    /**
     * Проверить все права.
     */
    function canAllPermissions(array $abilities, mixed $arguments = null, ?Model $user = null): bool
    {
        return app('unperm.gate')->all($abilities, $arguments, $user);
    }
}

if (!function_exists('isSuperadmin')) {
    /**
     * Проверить является ли пользователь суперадмином.
     */
    function isSuperadmin(?Model $user = null): bool
    {
        return app('unperm.gate')->isSuperAdmin($user);
    }
}

if (!function_exists('userCanResource')) {
    /**
     * Проверить может ли пользователь выполнить действие на ресурсе.
     */
    function userCanResource(Model $resource, string $action, ?Model $user = null): bool
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return false;
        }

        if (!method_exists($resource, 'userCan')) {
            throw new BadMethodCallException('Resource must use HasResourcePermissions trait');
        }

        return $resource->userCan($user, $action);
    }
}

if (!function_exists('authorizeResource')) {
    /**
     * Убедиться что пользователь может выполнить действие на ресурсе.
     *
     * @throws Illuminate\Auth\Access\AuthorizationException
     */
    function authorizeResource(Model $resource, string $action, ?Model $user = null): void
    {
        if (!userCanResource($resource, $action, $user)) {
            throw new Illuminate\Auth\Access\AuthorizationException(
                "You don't have permission to {$action} this resource."
            );
        }
    }
}

if (!function_exists('grantResourcePermission')) {
    /**
     * Предоставить доступ к ресурсу.
     */
    function grantResourcePermission(Model $user, Model $resource, string $action): void
    {
        DFiks\UnPerm\Support\ResourcePermission::grant($user, $resource, $action);
    }
}

if (!function_exists('revokeResourcePermission')) {
    /**
     * Отозвать доступ к ресурсу.
     */
    function revokeResourcePermission(Model $user, Model $resource, string $action): void
    {
        DFiks\UnPerm\Support\ResourcePermission::revoke($user, $resource, $action);
    }
}

if (!function_exists('usersWithResourceAccess')) {
    /**
     * Получить пользователей с доступом к ресурсу.
     */
    function usersWithResourceAccess(Model $resource, string $action): Illuminate\Support\Collection
    {
        return DFiks\UnPerm\Support\ResourcePermission::getUsersWithAccess($resource, $action);
    }
}

if (!function_exists('currentUserCan')) {
    /**
     * Проверить может ли текущий пользователь выполнить действие.
     */
    function currentUserCan(string $action): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        if (!method_exists($user, 'hasAction')) {
            return false;
        }

        return $user->hasAction($action);
    }
}

if (!function_exists('currentUserHasRole')) {
    /**
     * Проверить имеет ли текущий пользователь роль.
     */
    function currentUserHasRole(string $role): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        if (!method_exists($user, 'hasRole')) {
            return false;
        }

        return $user->hasRole($role);
    }
}

if (!function_exists('currentUserHasGroup')) {
    /**
     * Проверить принадлежит ли текущий пользователь группе.
     */
    function currentUserHasGroup(string $group): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        if (!method_exists($user, 'hasGroup')) {
            return false;
        }

        return $user->hasGroup($group);
    }
}

if (!function_exists('viewableResources')) {
    /**
     * Получить ресурсы доступные для просмотра текущему пользователю.
     */
    function viewableResources(string $modelClass): Illuminate\Database\Eloquent\Builder
    {
        $user = auth()->user();

        if (!$user) {
            return $modelClass::query()->whereRaw('1 = 0');
        }

        if (!method_exists($modelClass, 'viewableBy')) {
            throw new BadMethodCallException('Model must use HasResourcePermissions trait');
        }

        return $modelClass::viewableBy($user);
    }
}
