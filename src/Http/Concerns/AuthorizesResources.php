<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Http\Concerns;

use DFiks\UnPerm\Support\ResourcePermission;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

trait AuthorizesResources
{
    /**
     * Проверить право доступа текущего пользователя.
     *
     * @throws AuthorizationException
     */
    protected function authorizeAction(string $action, ?string $message = null): void
    {
        $user = auth()->user();

        if (!$user) {
            throw new AuthorizationException($message ?? 'Unauthenticated.');
        }

        if (!method_exists($user, 'hasAction')) {
            throw new AuthorizationException($message ?? 'Permission system not configured.');
        }

        if (!$user->hasAction($action)) {
            throw new AuthorizationException($message ?? "You don't have permission to: {$action}");
        }
    }

    /**
     * Проверить право доступа к ресурсу.
     *
     * @throws AuthorizationException
     */
    protected function authorizeResource(Model $resource, string $action, ?string $message = null): void
    {
        $user = auth()->user();

        if (!$user) {
            throw new AuthorizationException($message ?? 'Unauthenticated.');
        }

        if (!method_exists($resource, 'userCan')) {
            throw new AuthorizationException($message ?? 'Resource permissions not configured.');
        }

        if (!$resource->userCan($user, $action)) {
            $resourceName = class_basename($resource);
            throw new AuthorizationException(
                $message ?? "You don't have permission to {$action} this {$resourceName}."
            );
        }
    }

    /**
     * Проверить любое из прав доступа.
     *
     * @throws AuthorizationException
     */
    protected function authorizeAnyAction(array $actions, ?string $message = null): void
    {
        $user = auth()->user();

        if (!$user) {
            throw new AuthorizationException($message ?? 'Unauthenticated.');
        }

        foreach ($actions as $action) {
            if (method_exists($user, 'hasAction') && $user->hasAction($action)) {
                return;
            }
        }

        throw new AuthorizationException($message ?? 'You don\'t have any of required permissions.');
    }

    /**
     * Проверить роль пользователя.
     *
     * @throws AuthorizationException
     */
    protected function authorizeRole(string $role, ?string $message = null): void
    {
        $user = auth()->user();

        if (!$user) {
            throw new AuthorizationException($message ?? 'Unauthenticated.');
        }

        if (!method_exists($user, 'hasRole')) {
            throw new AuthorizationException($message ?? 'Role system not configured.');
        }

        if (!$user->hasRole($role)) {
            throw new AuthorizationException($message ?? "You must have role: {$role}");
        }
    }

    /**
     * Проверить группу пользователя.
     *
     * @throws AuthorizationException
     */
    protected function authorizeGroup(string $group, ?string $message = null): void
    {
        $user = auth()->user();

        if (!$user) {
            throw new AuthorizationException($message ?? 'Unauthenticated.');
        }

        if (!method_exists($user, 'hasGroup')) {
            throw new AuthorizationException($message ?? 'Group system not configured.');
        }

        if (!$user->hasGroup($group)) {
            throw new AuthorizationException($message ?? "You must be in group: {$group}");
        }
    }

    /**
     * Получить ресурсы доступные текущему пользователю.
     */
    protected function getViewableResources(string $modelClass)
    {
        $user = auth()->user();

        if (!$user) {
            return $modelClass::query()->whereRaw('1 = 0');
        }

        if (!method_exists($modelClass, 'viewableBy')) {
            return $modelClass::query();
        }

        return $modelClass::viewableBy($user);
    }

    /**
     * Предоставить доступ к ресурсу.
     */
    protected function grantResourceAccess(Model $user, Model $resource, string $action): void
    {
        ResourcePermission::grant($user, $resource, $action);
    }

    /**
     * Отозвать доступ к ресурсу.
     */
    protected function revokeResourceAccess(Model $user, Model $resource, string $action): void
    {
        ResourcePermission::revoke($user, $resource, $action);
    }

    /**
     * Отозвать все доступы к ресурсу.
     */
    protected function revokeAllResourceAccess(Model $user, Model $resource): void
    {
        ResourcePermission::revokeAll($user, $resource);
    }

    /**
     * Получить пользователей с доступом к ресурсу.
     */
    protected function getUsersWithAccess(Model $resource, string $action)
    {
        return ResourcePermission::getUsersWithAccess($resource, $action);
    }

    /**
     * Проверить является ли текущий пользователь супер-админом.
     */
    protected function isSuperAdmin(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        if (!method_exists($user, 'isSuperAdmin')) {
            return false;
        }

        return $user->isSuperAdmin();
    }

    /**
     * Вернуть JSON ответ с ошибкой авторизации.
     */
    protected function forbiddenResponse(?string $message = null): JsonResponse
    {
        return response()->json([
            'message' => $message ?? 'Forbidden.',
        ], Response::HTTP_FORBIDDEN);
    }

    /**
     * Вернуть JSON ответ для неаутентифицированного пользователя.
     */
    protected function unauthorizedResponse(?string $message = null): JsonResponse
    {
        return response()->json([
            'message' => $message ?? 'Unauthenticated.',
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Безопасно проверить право доступа (без исключений).
     */
    protected function canAction(string $action): bool
    {
        $user = auth()->user();

        if (!$user || !method_exists($user, 'hasAction')) {
            return false;
        }

        return $user->hasAction($action);
    }

    /**
     * Безопасно проверить право доступа к ресурсу (без исключений).
     */
    protected function canResource(Model $resource, string $action): bool
    {
        $user = auth()->user();

        if (!$user || !method_exists($resource, 'userCan')) {
            return false;
        }

        return $resource->userCan($user, $action);
    }

    /**
     * Безопасно проверить роль (без исключений).
     */
    protected function hasRole(string $role): bool
    {
        $user = auth()->user();

        if (!$user || !method_exists($user, 'hasRole')) {
            return false;
        }

        return $user->hasRole($role);
    }

    /**
     * Безопасно проверить группу (без исключений).
     */
    protected function hasGroup(string $group): bool
    {
        $user = auth()->user();

        if (!$user || !method_exists($user, 'hasGroup')) {
            return false;
        }

        return $user->hasGroup($group);
    }
}
