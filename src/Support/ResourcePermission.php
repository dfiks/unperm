<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Support;

use DFiks\UnPerm\Models\Action;
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

        $actionModel = self::createOrGet(
            $resource->getResourcePermissionKey(),
            $action,
            $resource->getResourcePermissionId(),
            $description
        );

        $user->assignAction($actionModel);

        // Перезагружаем связь actions чтобы изменения были видны сразу
        $user->load('actions');
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

        $slug = $resource->getResourcePermissionSlug($action);
        $actionModel = Action::where('slug', $slug)->first();

        if ($actionModel) {
            $user->removeAction($actionModel);
            // Перезагружаем связь actions чтобы изменения были видны сразу
            $user->load('actions');
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

        $resourceKey = $resource->getResourcePermissionKey();
        $resourceId = $resource->getResourcePermissionId();

        // Получаем все actions для этой записи
        $actions = Action::where('slug', 'like', "{$resourceKey}.%.{$resourceId}")
            ->get();

        foreach ($actions as $action) {
            $user->removeAction($action);
        }
        
        // Перезагружаем связь actions чтобы изменения были видны сразу
        $user->load('actions');
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
        if (!method_exists($resource, 'getResourcePermissionSlug')) {
            throw new BadMethodCallException('Resource model must use HasResourcePermissions trait');
        }

        $slug = $resource->getResourcePermissionSlug($action);
        $actionModel = Action::where('slug', $slug)->first();

        if (!$actionModel) {
            return collect([]);
        }

        // Получаем пользователей через полиморфную связь
        return DB::table('model_actions')
            ->where('action_id', $actionModel->id)
            ->where('model_type', $userModelClass)
            ->pluck('model_id')
            ->map(fn ($id) => $userModelClass::find($id))
            ->filter();
    }
}
