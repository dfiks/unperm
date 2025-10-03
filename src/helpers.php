<?php

use DFiks\UnPerm\Support\PermissionGate;
use Illuminate\Database\Eloquent\Model;

if (!function_exists('can_permission')) {
    /**
     * Проверить право доступа.
     */
    function can_permission(string $ability, mixed $arguments = null, ?Model $user = null): bool
    {
        return app('unperm.gate')->check($ability, $arguments, $user);
    }
}

if (!function_exists('authorize_permission')) {
    /**
     * Убедиться что есть право доступа (или throw exception).
     */
    function authorize_permission(string $ability, mixed $arguments = null, ?Model $user = null): void
    {
        app('unperm.gate')->authorize($ability, $arguments, $user);
    }
}

if (!function_exists('can_any_permission')) {
    /**
     * Проверить любое из прав.
     */
    function can_any_permission(array $abilities, mixed $arguments = null, ?Model $user = null): bool
    {
        return app('unperm.gate')->any($abilities, $arguments, $user);
    }
}

if (!function_exists('can_all_permissions')) {
    /**
     * Проверить все права.
     */
    function can_all_permissions(array $abilities, mixed $arguments = null, ?Model $user = null): bool
    {
        return app('unperm.gate')->all($abilities, $arguments, $user);
    }
}

if (!function_exists('is_superadmin')) {
    /**
     * Проверить является ли пользователь суперадмином.
     */
    function is_superadmin(?Model $user = null): bool
    {
        return app('unperm.gate')->isSuperAdmin($user);
    }
}

