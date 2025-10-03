<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void define(string $ability, \Closure|array|string $callback)
 * @method static bool|PermissionResult check(string $ability, mixed $arguments = null, ?\Illuminate\Database\Eloquent\Model $user = null, bool $fluent = false)
 * @method static \DFiks\UnPerm\Support\PermissionResult can(string $ability, mixed $arguments = null, ?\Illuminate\Database\Eloquent\Model $user = null)
 * @method static void before(\Closure $callback)
 * @method static void after(\Closure $callback)
 * @method static bool|PermissionResult any(array $abilities, mixed $arguments = null, ?\Illuminate\Database\Eloquent\Model $user = null, bool $fluent = false)
 * @method static bool|PermissionResult all(array $abilities, mixed $arguments = null, ?\Illuminate\Database\Eloquent\Model $user = null, bool $fluent = false)
 * @method static \DFiks\UnPerm\Support\PermissionResult canAny(array $abilities, mixed $arguments = null, ?\Illuminate\Database\Eloquent\Model $user = null)
 * @method static \DFiks\UnPerm\Support\PermissionResult canAll(array $abilities, mixed $arguments = null, ?\Illuminate\Database\Eloquent\Model $user = null)
 * @method static void authorize(string $ability, mixed $arguments = null, ?\Illuminate\Database\Eloquent\Model $user = null)
 * @method static array getRules()
 * @method static bool isSuperAdmin(?\Illuminate\Database\Eloquent\Model $user = null)
 * @method static ?string getSuperAdminReason(?\Illuminate\Database\Eloquent\Model $user = null)
 *
 * @see \DFiks\UnPerm\Support\PermissionGate
 */
class PermissionGate extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'unperm.gate';
    }
}

