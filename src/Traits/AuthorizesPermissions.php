<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Traits;

use Illuminate\Support\Facades\Gate;

/**
 * Trait для контроллеров с декларативными правилами доступа.
 */
trait AuthorizesPermissions
{
    /**
     * Определить правила доступа для действий контроллера.
     *
     * @return array<string, array|string|callable>
     *
     * Примеры:
     * [
     *     'index' => 'users.view',
     *     'store' => ['users.create'],
     *     'update' => ['require_all' => ['users.edit', 'users.update']],
     *     'destroy' => fn($user, $model) => $user->id === $model->owner_id,
     * ]
     */
    protected function permissionRules(): array
    {
        return [];
    }

    /**
     * Инициализация правил при создании контроллера.
     */
    public function __construct()
    {
        $this->registerPermissionRules();

        // Вызываем родительский конструктор если есть
        if (method_exists(get_parent_class($this), '__construct')) {
            parent::__construct();
        }
    }

    /**
     * Регистрировать правила в Permission Gate.
     */
    protected function registerPermissionRules(): void
    {
        $rules = $this->permissionRules();

        foreach ($rules as $action => $rule) {
            $abilityName = $this->makeAbilityName($action);

            // Регистрируем middleware для этого action
            $this->middleware(function ($request, $next) use ($abilityName, $action) {
                // Получаем параметры из роута
                $routeParameters = $request->route()->parameters();
                $resource = !empty($routeParameters) ? reset($routeParameters) : null;

                // Проверяем права
                if (!app('unperm.gate')->check($abilityName, $resource)) {
                    abort(403, "У вас нет прав для выполнения действия: {$action}");
                }

                return $next($request);
            })->only($action);

            // Также регистрируем в Permission Gate
            app('unperm.gate')->define($abilityName, $rule);
        }
    }

    /**
     * Создать название ability из действия контроллера.
     */
    protected function makeAbilityName(string $action): string
    {
        $controller = class_basename(static::class);
        $controller = str_replace('Controller', '', $controller);
        $controller = \Illuminate\Support\Str::kebab($controller);

        return "{$action}-{$controller}";
    }

    /**
     * Проверить права для текущего пользователя с fluent API.
     */
    protected function can(string $ability, mixed $arguments = null): \DFiks\UnPerm\Support\PermissionResult
    {
        return app('unperm.gate')->can($ability, $arguments);
    }

    /**
     * Убедиться что пользователь имеет права (или throw exception).
     */
    protected function authorize(string $ability, mixed $arguments = null): void
    {
        app('unperm.gate')->authorize($ability, $arguments);
    }

    /**
     * Проверить любое из прав с fluent API.
     */
    protected function canAny(array $abilities, mixed $arguments = null): \DFiks\UnPerm\Support\PermissionResult
    {
        return app('unperm.gate')->canAny($abilities, $arguments);
    }

    /**
     * Проверить все права с fluent API.
     */
    protected function canAll(array $abilities, mixed $arguments = null): \DFiks\UnPerm\Support\PermissionResult
    {
        return app('unperm.gate')->canAll($abilities, $arguments);
    }
}
