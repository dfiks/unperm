<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Services\Concerns;

use Illuminate\Auth\Access\AuthorizationException;

trait AuthorizesServiceActions
{
    /**
     * Отключить проверку прав для системных операций.
     */
    protected bool $skipAuthorization = false;

    /**
     * Отключить проверку прав.
     */
    public function withoutAuthorization(): static
    {
        $this->skipAuthorization = true;

        return $this;
    }

    /**
     * Включить проверку прав.
     */
    public function withAuthorization(): static
    {
        $this->skipAuthorization = false;

        return $this;
    }

    /**
     * Проверить имеет ли текущий пользователь право.
     *
     * @throws AuthorizationException
     */
    protected function authorize(string $action, ?string $message = null): void
    {
        if ($this->skipAuthorization || !$this->isAuthorizationEnabled()) {
            return;
        }

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
     * Проверить включена ли авторизация в конфигурации.
     */
    protected function isAuthorizationEnabled(): bool
    {
        return config('unperm.service_authorization_enabled', false);
    }

    /**
     * Проверить является ли пользователь супер-админом.
     */
    protected function isSuperAdmin(): bool
    {
        if ($this->skipAuthorization || !$this->isAuthorizationEnabled()) {
            return true;
        }

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
     * Проверить может ли пользователь выполнить действие (без exception).
     */
    protected function can(string $action): bool
    {
        if ($this->skipAuthorization || !$this->isAuthorizationEnabled()) {
            return true;
        }

        $user = auth()->user();

        if (!$user || !method_exists($user, 'hasAction')) {
            return false;
        }

        return $user->hasAction($action);
    }
}
