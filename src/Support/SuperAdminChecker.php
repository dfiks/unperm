<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Support;

use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Проверка суперадминистраторов.
 */
class SuperAdminChecker
{
    /**
     * Статический метод для быстрой проверки.
     */
    public static function isSuperAdmin(Model $user): bool
    {
        return (new static())->check($user);
    }

    /**
     * Проверить является ли пользователь суперадмином.
     */
    public function check(Model $user): bool
    {
        $config = config('unperm.superadmins', []);

        if (!($config['enabled'] ?? true)) {
            return false;
        }

        // 1. Проверка по модели
        if ($this->checkByModel($user, $config)) {
            return true;
        }

        // 2. Проверка по ID
        if ($this->checkById($user, $config)) {
            return true;
        }

        // 3. Проверка по email
        if ($this->checkByEmail($user, $config)) {
            return true;
        }

        // 4. Проверка по username
        if ($this->checkByUsername($user, $config)) {
            return true;
        }

        // 5. Проверка через метод модели
        if ($this->checkByMethod($user, $config)) {
            return true;
        }

        // 6. Проверка по action
        if ($this->checkByAction($user, $config)) {
            return true;
        }

        // 7. Проверка через callback
        if ($this->checkByCallback($user, $config)) {
            return true;
        }

        return false;
    }

    /**
     * Проверка по классу модели.
     */
    protected function checkByModel(Model $user, array $config): bool
    {
        $models = $config['models'] ?? [];

        if (empty($models)) {
            return false;
        }

        $userClass = get_class($user);

        foreach ($models as $modelClass) {
            if ($userClass === $modelClass || is_a($user, $modelClass)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверка по ID.
     */
    protected function checkById(Model $user, array $config): bool
    {
        $ids = $config['ids'] ?? [];

        if (empty($ids)) {
            return false;
        }

        $userId = $user->getKey();

        return in_array($userId, $ids, false); // Не строгое сравнение для UUID
    }

    /**
     * Проверка по email.
     */
    protected function checkByEmail(Model $user, array $config): bool
    {
        $emails = $config['emails'] ?? [];

        if (empty($emails)) {
            return false;
        }

        $userEmail = $user->email ?? null;

        if (!$userEmail) {
            return false;
        }

        return in_array($userEmail, $emails, true);
    }

    /**
     * Проверка по username.
     */
    protected function checkByUsername(Model $user, array $config): bool
    {
        $usernames = $config['usernames'] ?? [];

        if (empty($usernames)) {
            return false;
        }

        $username = $user->username ?? $user->login ?? null;

        if (!$username) {
            return false;
        }

        return in_array($username, $usernames, true);
    }

    /**
     * Проверка через метод модели.
     */
    protected function checkByMethod(Model $user, array $config): bool
    {
        $method = $config['check_method'] ?? null;

        if (!$method || !method_exists($user, $method)) {
            return false;
        }

        try {
            return (bool) $user->{$method}();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Проверка по action.
     */
    protected function checkByAction(Model $user, array $config): bool
    {
        $action = $config['action'] ?? null;

        if (!$action) {
            return false;
        }

        if (!method_exists($user, 'hasAction')) {
            return false;
        }

        try {
            return $user->hasAction($action);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Проверка через callback.
     */
    protected function checkByCallback(Model $user, array $config): bool
    {
        $callback = $config['callback'] ?? null;

        if (!$callback || !is_callable($callback)) {
            return false;
        }

        try {
            return (bool) $callback($user);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Получить причину почему пользователь суперадмин (для отладки).
     */
    public function getReason(Model $user): ?string
    {
        $config = config('unperm.superadmins', []);

        if (!($config['enabled'] ?? true)) {
            return null;
        }

        if ($this->checkByModel($user, $config)) {
            return 'Модель ' . get_class($user) . ' в списке суперадминов';
        }

        if ($this->checkById($user, $config)) {
            return 'ID ' . $user->getKey() . ' в списке суперадминов';
        }

        if ($this->checkByEmail($user, $config)) {
            return 'Email ' . ($user->email ?? 'N/A') . ' в списке суперадминов';
        }

        if ($this->checkByUsername($user, $config)) {
            return 'Username ' . ($user->username ?? $user->login ?? 'N/A') . ' в списке суперадминов';
        }

        if ($this->checkByMethod($user, $config)) {
            $method = $config['check_method'];

            return "Метод {$method}() вернул true";
        }

        if ($this->checkByAction($user, $config)) {
            return 'Имеет action: ' . $config['action'];
        }

        if ($this->checkByCallback($user, $config)) {
            return 'Callback вернул true';
        }

        return null;
    }
}
