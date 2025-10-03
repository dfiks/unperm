<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Support;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Класс для управления проверками прав доступа.
 */
class PermissionGate
{
    protected array $rules = [];
    protected array $beforeCallbacks = [];
    protected array $afterCallbacks = [];
    protected ?SuperAdminChecker $superAdminChecker = null;

    public function __construct()
    {
        $this->superAdminChecker = new SuperAdminChecker();
        
        // Регистрируем проверку суперадминов как before callback
        $this->before(function ($user, $ability, $arguments) {
            if ($this->superAdminChecker->check($user)) {
                return true;
            }
            return null;
        });
    }

    /**
     * Определить правило доступа.
     *
     * @param string $ability Название способности (например, 'view-users', 'edit-post')
     * @param Closure|array|string $callback Правило проверки
     */
    public function define(string $ability, Closure|array|string $callback): void
    {
        $this->rules[$ability] = $callback;
    }

    /**
     * Проверить право доступа.
     *
     * @param string $ability Название способности
     * @param mixed $arguments Аргументы для проверки (обычно модель)
     * @param Model|null $user Пользователь (если null - текущий)
     * @param bool $fluent Вернуть PermissionResult вместо bool
     * @return bool|PermissionResult
     */
    public function check(string $ability, mixed $arguments = null, ?Model $user = null, bool $fluent = false): bool|PermissionResult
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return false;
        }

        // Проверяем before callbacks
        foreach ($this->beforeCallbacks as $callback) {
            $result = $callback($user, $ability, $arguments);
            if ($result !== null) {
                return (bool) $result;
            }
        }

        // Проверяем само правило
        if (!isset($this->rules[$ability])) {
            return false;
        }

        $rule = $this->rules[$ability];
        $result = $this->evaluateRule($rule, $user, $arguments);

        // Проверяем after callbacks
        foreach ($this->afterCallbacks as $callback) {
            $afterResult = $callback($user, $ability, $result, $arguments);
            if ($afterResult !== null) {
                $result = (bool) $afterResult;
            }
        }

        return $fluent ? PermissionResult::make($result, $ability, $arguments) : $result;
    }

    /**
     * Проверить право доступа с fluent API.
     *
     * @param string $ability Название способности
     * @param mixed $arguments Аргументы для проверки
     * @param Model|null $user Пользователь
     * @return PermissionResult
     */
    public function can(string $ability, mixed $arguments = null, ?Model $user = null): PermissionResult
    {
        return $this->check($ability, $arguments, $user, fluent: true);
    }

    /**
     * Вычислить правило.
     */
    protected function evaluateRule(Closure|array|string $rule, Model $user, mixed $arguments): bool
    {
        // Closure
        if ($rule instanceof Closure) {
            return (bool) $rule($user, $arguments);
        }

        // Массив actions
        if (is_array($rule)) {
            return $this->checkActions($user, $rule, $arguments);
        }

        // Строка - один action
        if (is_string($rule)) {
            return $this->checkAction($user, $rule, $arguments);
        }

        return false;
    }

    /**
     * Проверить actions.
     */
    protected function checkActions(Model $user, array $actions, mixed $arguments): bool
    {
        // Поддерживаем разные форматы:
        // ['users.view'] - хотя бы один action
        // ['require_all' => ['users.view', 'users.edit']] - все actions
        // ['require_any' => ['users.view', 'posts.view']] - хотя бы один
        
        if (isset($actions['require_all'])) {
            return method_exists($user, 'hasAllActions') 
                ? $user->hasAllActions($actions['require_all'])
                : false;
        }

        if (isset($actions['require_any'])) {
            return method_exists($user, 'hasAnyAction')
                ? $user->hasAnyAction($actions['require_any'])
                : false;
        }

        // По умолчанию - хотя бы один action
        return method_exists($user, 'hasAnyAction')
            ? $user->hasAnyAction($actions)
            : false;
    }

    /**
     * Проверить один action.
     */
    protected function checkAction(Model $user, string $action, mixed $arguments): bool
    {
        if (!method_exists($user, 'hasAction')) {
            return false;
        }

        // Если передана модель с resource permissions
        if ($arguments instanceof Model && method_exists($arguments, 'userCan')) {
            // Извлекаем action name из полного ability
            // Например: 'view-folder' -> 'view'
            $actionName = $this->extractActionName($action);
            return $arguments->userCan($user, $actionName);
        }

        return $user->hasAction($action);
    }

    /**
     * Извлечь имя действия из ability.
     */
    protected function extractActionName(string $ability): string
    {
        // 'view-folder' -> 'view'
        // 'edit-post' -> 'edit'
        if (preg_match('/^([a-z]+)-/', $ability, $matches)) {
            return $matches[1];
        }

        return $ability;
    }

    /**
     * Добавить callback который выполняется перед проверкой.
     */
    public function before(Closure $callback): void
    {
        $this->beforeCallbacks[] = $callback;
    }

    /**
     * Добавить callback который выполняется после проверки.
     */
    public function after(Closure $callback): void
    {
        $this->afterCallbacks[] = $callback;
    }

    /**
     * Проверить любое из прав.
     */
    public function any(array $abilities, mixed $arguments = null, ?Model $user = null, bool $fluent = false): bool|PermissionResult
    {
        $result = false;
        $abilityName = implode(' OR ', $abilities);

        foreach ($abilities as $ability) {
            if ($this->check($ability, $arguments, $user)) {
                $result = true;
                break;
            }
        }

        return $fluent ? PermissionResult::make($result, $abilityName, $arguments) : $result;
    }

    /**
     * Проверить все права.
     */
    public function all(array $abilities, mixed $arguments = null, ?Model $user = null, bool $fluent = false): bool|PermissionResult
    {
        $result = true;
        $abilityName = implode(' AND ', $abilities);

        foreach ($abilities as $ability) {
            if (!$this->check($ability, $arguments, $user)) {
                $result = false;
                break;
            }
        }

        return $fluent ? PermissionResult::make($result, $abilityName, $arguments) : $result;
    }

    /**
     * Проверить любое из прав с fluent API.
     */
    public function canAny(array $abilities, mixed $arguments = null, ?Model $user = null): PermissionResult
    {
        return $this->any($abilities, $arguments, $user, fluent: true);
    }

    /**
     * Проверить все права с fluent API.
     */
    public function canAll(array $abilities, mixed $arguments = null, ?Model $user = null): PermissionResult
    {
        return $this->all($abilities, $arguments, $user, fluent: true);
    }

    /**
     * Убедиться что пользователь имеет право (throw exception).
     */
    public function authorize(string $ability, mixed $arguments = null, ?Model $user = null): void
    {
        if (!$this->check($ability, $arguments, $user)) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException(
                "Unauthorized action: {$ability}"
            );
        }
    }

    /**
     * Получить все зарегистрированные правила.
     */
    public function getRules(): array
    {
        return array_keys($this->rules);
    }

    /**
     * Проверить является ли пользователь суперадмином.
     */
    public function isSuperAdmin(?Model $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return false;
        }

        return $this->superAdminChecker->check($user);
    }

    /**
     * Получить причину почему пользователь суперадмин.
     */
    public function getSuperAdminReason(?Model $user = null): ?string
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return null;
        }

        return $this->superAdminChecker->getReason($user);
    }
}


