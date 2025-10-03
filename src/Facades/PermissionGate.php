<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void define(string $ability, \Closure|array|string $callback)
 * @method static bool check(string $ability, mixed $arguments = null, ?\Illuminate\Database\Eloquent\Model $user = null)
 * @method static void before(\Closure $callback)
 * @method static void after(\Closure $callback)
 * @method static bool any(array $abilities, mixed $arguments = null, ?\Illuminate\Database\Eloquent\Model $user = null)
 * @method static bool all(array $abilities, mixed $arguments = null, ?\Illuminate\Database\Eloquent\Model $user = null)
 * @method static void authorize(string $ability, mixed $arguments = null, ?\Illuminate\Database\Eloquent\Model $user = null)
 * @method static array getRules()
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

