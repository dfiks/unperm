<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Support;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\ResourceAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use BadMethodCallException;

/**
 * Helper для работы с resource permissions (точечными разрешениями).
 */
class ResourcePermission
{
    /**
     * Создать или получить action для конкретной записи.
     *
     * @param  string      $resourceKey Ключ ресурса (folders, posts, etc.)
     * @param  string      $action      Действие (view, edit, delete, etc.)
     * @param  string      $resourceId  ID записи
     * @param  string|null $description Описание
     * @return Action
     */
    public static function createOrGet(
        string $resourceKey,
        string $action,
        string $resourceId,
        ?string $description = null
    ): Action {
        $slug = self::makeSlug($resourceKey, $action, $resourceId);

        $actionModel = Action::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $description ?? ucfirst("{$action} {$resourceKey} {$resourceId}"),
                'description' => $description,
                'bitmask' => '0',
            ]
        );

        // Если action только что создан, обновляем все bitmask
        if ($actionModel->wasRecentlyCreated) {
            PermBit::rebuild();
            // Перезагружаем action из БД чтобы получить обновленный bitmask
            $actionModel->refresh();
        }

        return $actionModel;
    }

    /**
     * Создать slug для resource permission.
     */
    public static function makeSlug(string $resourceKey, string $action, string $resourceId): string
    {
        return "{$resourceKey}.{$action}.{$resourceId}";
    }

    /**
     * Назначить пользователю разрешение на конкретную запись.
     *
     * @param Model       $user        Пользователь (с HasPermissions trait)
     * @param Model       $resource    Ресурс (с HasResourcePermissions trait)
     * @param string      $action      Действие
     * @param string|null $description Описание
     */
    public static function grant(
        Model $user,
        Model $resource,
        string $action,
        ?string $description = null
    ): void {
        if (!method_exists($user, 'assignAction')) {
            throw new BadMethodCallException('User model must use HasPermissions trait');
        }

        if (!method_exists($resource, 'getResourcePermissionSlug')) {
            throw new BadMethodCallException('Resource model must use HasResourcePermissions trait');
        }

        // Создаем или получаем ResourceAction
        $resourceAction = ResourceAction::findOrCreateForResource($resource, $action);

        // Если action только что создан, обновляем bitmask
        if ($resourceAction->wasRecentlyCreated) {
            PermBit::rebuild();
            $resourceAction->refresh();
        }

        $user->assignAction($resourceAction);

        // Перезагружаем связи чтобы изменения были видны сразу
        if (method_exists($user, 'resourceActions')) {
            $user->load('resourceActions');
        }
    }

    /**
     * Отозвать у пользователя разрешение на конкретную запись.
     */
    public static function revoke(Model $user, Model $resource, string $action): void
    {
        if (!method_exists($user, 'removeAction')) {
            throw new BadMethodCallException('User model must use HasPermissions trait');
        }

        if (!method_exists($resource, 'getResourcePermissionSlug')) {
            throw new BadMethodCallException('Resource model must use HasResourcePermissions trait');
        }

        $resourceAction = ResourceAction::where('resource_type', get_class($resource))
            ->where('resource_id', $resource->getKey())
            ->where('action_type', $action)
            ->first();

        if ($resourceAction) {
            $user->removeAction($resourceAction);
            // Перезагружаем связи чтобы изменения были видны сразу
            if (method_exists($user, 'resourceActions')) {
                $user->load('resourceActions');
            }
        }
    }

    /**
     * Назначить все базовые CRUD разрешения на ресурс.
     *
     * @param Model $user
     * @param Model $resource
     * @param array $actions  По умолчанию: view, create, edit, delete
     */
    public static function grantCrud(
        Model $user,
        Model $resource,
        array $actions = ['view', 'edit', 'delete']
    ): void {
        foreach ($actions as $action) {
            self::grant($user, $resource, $action);
        }
    }

    /**
     * Отозвать все разрешения пользователя на конкретную запись.
     */
    public static function revokeAll(Model $user, Model $resource): void
    {
        if (!method_exists($resource, 'getResourcePermissionKey')) {
            throw new BadMethodCallException('Resource model must use HasResourcePermissions trait');
        }

        // Получаем все resource actions для этой записи
        $resourceActions = ResourceAction::where('resource_type', get_class($resource))
            ->where('resource_id', $resource->getKey())
            ->get();

        foreach ($resourceActions as $action) {
            $user->removeAction($action);
        }

        // Перезагружаем связи чтобы изменения были видны сразу
        if (method_exists($user, 'resourceActions')) {
            $user->load('resourceActions');
        }
    }

    /**
     * Массовое назначение разрешений для группы пользователей.
     *
     * @param array        $users    Массив пользователей
     * @param Model        $resource Ресурс
     * @param string|array $actions  Действие или массив действий
     */
    public static function grantToMany(array $users, Model $resource, string|array $actions): void
    {
        $actions = is_array($actions) ? $actions : [$actions];

        DB::transaction(function () use ($users, $resource, $actions) {
            foreach ($users as $user) {
                foreach ($actions as $action) {
                    self::grant($user, $resource, $action);
                }
            }
        });
    }

    /**
     * Получить всех пользователей, у которых есть конкретное разрешение на ресурс.
     *
     * @param  Model                          $resource
     * @param  string                         $action
     * @param  string                         $userModelClass Класс модели пользователя
     * @return \Illuminate\Support\Collection
     */
    public static function getUsersWithAccess(
        Model $resource,
        string $action,
        string $userModelClass
    ): \Illuminate\Support\Collection {
        $resourceAction = ResourceAction::where('resource_type', get_class($resource))
            ->where('resource_id', $resource->getKey())
            ->where('action_type', $action)
            ->first();

        if (!$resourceAction) {
            return collect([]);
        }

        // Получаем ID пользователей из pivot таблицы
        $userIds = \DB::table('model_resource_actions')
            ->where('resource_action_id', $resourceAction->id)
            ->where('model_type', $userModelClass)
            ->pluck('model_id');

        if ($userIds->isEmpty()) {
            return collect([]);
        }

        // Загружаем сами модели пользователей
        return $userModelClass::whereIn('id', $userIds)->get();
    }

    /**
     * Получить все resource actions для конкретного ресурса.
     *
     * @param  Model                                    $resource
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getResourceActions(Model $resource): \Illuminate\Database\Eloquent\Collection
    {
        return ResourceAction::getForResource($resource);
    }
}
