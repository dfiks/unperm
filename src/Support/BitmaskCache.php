<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Менеджер кеша для битовых масок.
 *
 * Управляет кешированием через Redis для оптимизации производительности
 */
class BitmaskCache
{
    /**
     * Получить агрегированную битовую маску пользователя с кешированием
     *
     * @param  mixed  $model Модель с HasPermissions trait
     * @return string Битовая маска
     */
    public static function getUserBitmask($model): string
    {
        $key = self::getUserBitmaskKey($model);
        $ttl = config('unperm.cache.ttl_bitmask', 3600);

        return Cache::remember($key, $ttl, function () use ($model) {
            // Вызываем напрямую calculatePermissionBitmask чтобы избежать рекурсии
            return method_exists($model, 'calculatePermissionBitmask')
                ? $model->calculatePermissionBitmask()
                : $model->getPermissionBitmask();
        });
    }

    /**
     * Очистить кеш битовой маски пользователя.
     * @param mixed $model
     */
    public static function clearUserBitmask($model): void
    {
        if (config('unperm.cache.enabled')) {
            $key = self::getUserBitmaskKey($model);
            Cache::forget($key);
        }
    }

    /**
     * Очистить все связанные кеши при изменении разрешений.
     * @param mixed $model
     */
    public static function clearRelatedCaches($model): void
    {
        if (!config('unperm.cache.enabled')) {
            return;
        }

        // Очищаем кеш самой модели
        self::clearUserBitmask($model);

        // Очищаем кеши связанных ролей
        if (method_exists($model, 'roles')) {
            foreach ($model->roles as $role) {
                $key = self::getModelCacheKey($role);
                Cache::forget($key);
            }
        }

        // Очищаем кеши связанных групп
        if (method_exists($model, 'groups')) {
            foreach ($model->groups as $group) {
                $key = self::getModelCacheKey($group);
                Cache::forget($key);
            }
        }
    }

    /**
     * Получить ключ кеша для битовой маски пользователя.
     * @param mixed $model
     */
    protected static function getUserBitmaskKey($model): string
    {
        $prefix = config('unperm.cache.prefix', 'unperm');
        $type = class_basename($model);
        $id = $model->id ?? 'unsaved';

        return "{$prefix}:user_bitmask:{$type}:{$id}";
    }

    /**
     * Получить ключ кеша для модели.
     * @param mixed $model
     */
    protected static function getModelCacheKey($model): string
    {
        $prefix = config('unperm.cache.prefix', 'unperm');
        $type = class_basename($model);
        $id = $model->id ?? 'unsaved';

        return "{$prefix}:model_bitmask:{$type}:{$id}";
    }

    /**
     * Кешировать результат проверки разрешения.
     *
     * @param  mixed    $model
     * @param  string   $action
     * @param  callable $callback
     * @return bool
     */
    public static function rememberPermissionCheck($model, string $action, callable $callback): bool
    {
        if (!config('unperm.cache.enabled')) {
            return $callback();
        }

        $key = self::getPermissionCheckKey($model, $action);
        $ttl = config('unperm.cache.ttl_bitmask', 3600);

        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Очистить кеш проверок разрешений для модели.
     * @param mixed $model
     */
    public static function clearPermissionChecks($model): void
    {
        if (!config('unperm.cache.enabled')) {
            return;
        }

        $prefix = config('unperm.cache.prefix', 'unperm');
        $type = class_basename($model);
        $id = $model->id ?? 'unsaved';

        // Очищаем все ключи с префиксом
        $pattern = "{$prefix}:perm_check:{$type}:{$id}:*";

        // Для Redis
        if (config('unperm.cache.driver') === 'redis') {
            $redis = Cache::getRedis();
            $keys = $redis->keys($pattern);
            if (!empty($keys)) {
                $redis->del($keys);
            }
        }
    }

    /**
     * Получить ключ кеша для проверки разрешения.
     * @param mixed $model
     */
    protected static function getPermissionCheckKey($model, string $action): string
    {
        $prefix = config('unperm.cache.prefix', 'unperm');
        $type = class_basename($model);
        $id = $model->id ?? 'unsaved';

        return "{$prefix}:perm_check:{$type}:{$id}:{$action}";
    }

    /**
     * Очистить весь кеш UnPerm.
     */
    public static function flush(): void
    {
        if (!config('unperm.cache.enabled')) {
            return;
        }

        $prefix = config('unperm.cache.prefix', 'unperm');

        // Для Redis
        if (config('unperm.cache.driver') === 'redis') {
            $redis = Cache::getRedis();
            $keys = $redis->keys("{$prefix}:*");
            if (!empty($keys)) {
                $redis->del($keys);
            }
        }
    }

    /**
     * Получить статистику кеша.
     */
    public static function getStats(): array
    {
        if (!config('unperm.cache.enabled')) {
            return [
                'enabled' => false,
                'driver' => null,
                'keys_count' => 0,
            ];
        }

        $prefix = config('unperm.cache.prefix', 'unperm');
        $driver = config('unperm.cache.driver');
        $keysCount = 0;

        // Для Redis
        if ($driver === 'redis') {
            $redis = Cache::getRedis();
            $keys = $redis->keys("{$prefix}:*");
            $keysCount = count($keys);
        }

        return [
            'enabled' => true,
            'driver' => $driver,
            'keys_count' => $keysCount,
            'prefix' => $prefix,
            'ttl_bitmask' => config('unperm.cache.ttl_bitmask'),
            'ttl_sparse_bits' => config('unperm.cache.ttl_sparse_bits'),
        ];
    }
}
