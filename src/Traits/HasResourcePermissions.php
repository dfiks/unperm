<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use BadMethodCallException;

/**
 * Trait для моделей с точечными разрешениями на уровне записей.
 *
 * Позволяет назначать права на конкретные записи:
 * - folders.view.{uuid}
 * - posts.edit.{id}
 * - documents.delete.{ulid}
 *
 * @property string $resourcePermissionKey Ключ для разрешений (например, 'folders', 'posts')
 */
trait HasResourcePermissions
{
    /**
     * Получить resource key для разрешений.
     * По умолчанию - table name во множественном числе.
     */
    public function getResourcePermissionKey(): string
    {
        return $this->resourcePermissionKey ?? $this->getTable();
    }

    /**
     * Получить идентификатор для resource permission.
     */
    public function getResourcePermissionId(): string
    {
        return (string) $this->getKey();
    }

    /**
     * Сгенерировать action slug для конкретного действия над этой записью.
     *
     * @param  string $action Действие (view, edit, delete, etc.)
     * @return string Полный slug (например, "folders.view.uuid-123")
     */
    public function getResourcePermissionSlug(string $action): string
    {
        return sprintf(
            '%s.%s.%s',
            $this->getResourcePermissionKey(),
            $action,
            $this->getResourcePermissionId()
        );
    }

    /**
     * Проверить может ли пользователь выполнить действие над этой записью.
     *
     * @param  Model  $user   Пользователь (должен иметь HasPermissions trait)
     * @param  string $action Действие (view, edit, delete, etc.)
     * @return bool
     */
    public function userCan(Model $user, string $action): bool
    {
        if (!method_exists($user, 'hasAction')) {
            throw new BadMethodCallException('User model must use HasPermissions trait');
        }

        // Проверяем точечное разрешение на конкретную запись через прямой запрос к БД
        // Так как dynamic resource permissions могут не быть в PermBit кеше
        $resourceSlug = $this->getResourcePermissionSlug($action);
        if ($this->userHasActionBySlug($user, $resourceSlug)) {
            return true;
        }

        // Проверяем общее разрешение на действие (wildcard)
        $wildcardSlug = sprintf('%s.%s', $this->getResourcePermissionKey(), $action);
        if ($this->userHasActionBySlug($user, $wildcardSlug)) {
            return true;
        }

        // Проверяем полный wildcard на все действия
        $fullWildcardSlug = sprintf('%s.*', $this->getResourcePermissionKey());
        if ($this->userHasActionBySlug($user, $fullWildcardSlug)) {
            return true;
        }

        return false;
    }

    /**
     * Проверить есть ли у пользователя action по slug (обходя PermBit кеш).
     */
    protected function userHasActionBySlug(Model $user, string $slug): bool
    {
        // Сначала проверяем в загруженных actions (eager loaded)
        if ($user->relationLoaded('actions')) {
            foreach ($user->actions as $action) {
                if ($action->slug === $slug) {
                    return true;
                }
            }
            return false;
        }

        // Иначе делаем запрос к БД
        return $user->actions()->where('slug', $slug)->exists();
    }

    /**
     * Scope для фильтрации записей по разрешениям пользователя.
     *
     * @param  Builder $query
     * @param  Model   $user   Пользователь с HasPermissions trait
     * @param  string  $action Действие (view, edit, delete, etc.)
     * @return Builder
     */
    public function scopeWhereUserCan(Builder $query, Model $user, string $action): Builder
    {
        if (!method_exists($user, 'hasAction')) {
            throw new BadMethodCallException('User model must use HasPermissions trait');
        }

        $resourceKey = $this->getResourcePermissionKey();
        $wildcardSlug = sprintf('%s.%s', $resourceKey, $action);
        $fullWildcardSlug = sprintf('%s.*', $resourceKey);

        // Если у пользователя есть wildcard разрешение - возвращаем все записи
        if ($this->userHasActionBySlug($user, $wildcardSlug) || $this->userHasActionBySlug($user, $fullWildcardSlug)) {
            return $query;
        }

        // Иначе фильтруем только те записи, на которые есть точечные права
        $userActions = $user->actions()
            ->where('slug', 'like', "{$resourceKey}.{$action}.%")
            ->pluck('slug')
            ->map(function ($slug) use ($resourceKey, $action) {
                // Извлекаем ID из slug: "folders.view.uuid-123" -> "uuid-123"
                $prefix = "{$resourceKey}.{$action}.";
                if (str_starts_with($slug, $prefix)) {
                    return substr($slug, strlen($prefix));
                }

                return null;
            })
            ->filter()
            ->toArray();

        if (empty($userActions)) {
            // Если нет точечных прав - возвращаем пустой результат
            return $query->whereRaw('1 = 0');
        }

        // Фильтруем по ID записей, на которые есть права
        return $query->whereIn($this->getKeyName(), $userActions);
    }

    /**
     * Scope для получения только тех записей, которые пользователь может просмотреть.
     */
    public function scopeViewableBy(Builder $query, Model $user): Builder
    {
        return $this->scopeWhereUserCan($query, $user, 'view');
    }

    /**
     * Scope для получения только тех записей, которые пользователь может редактировать.
     */
    public function scopeEditableBy(Builder $query, Model $user): Builder
    {
        return $this->scopeWhereUserCan($query, $user, 'edit');
    }

    /**
     * Scope для получения только тех записей, которые пользователь может удалить.
     */
    public function scopeDeletableBy(Builder $query, Model $user): Builder
    {
        return $this->scopeWhereUserCan($query, $user, 'delete');
    }
}
