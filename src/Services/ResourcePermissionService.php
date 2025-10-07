<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Services;

use DFiks\UnPerm\Services\Concerns\AuthorizesServiceActions;
use DFiks\UnPerm\Support\ResourcePermission;
use Illuminate\Database\Eloquent\Model;

class ResourcePermissionService
{
    use AuthorizesServiceActions;

    /**
     * Получить текущую модель доступа к зависимому ресурсу для пользователя.
     * Возвращает режим и список выбранных дочерних ID.
     */
    public function getAccessForDependentResource(
        Model $user,
        Model $parentResource,
        string $childResourceKey,
        string $action,
        string $childModelClass
    ): array {
        $dependency = config('unperm.resource_dependencies.' . $childResourceKey, []);
        $parentAction = $dependency['actions'][$action] ?? $action;

        $hasFull = method_exists($parentResource, 'userCan')
            ? $parentResource->userCan($user, $parentAction)
            : false;

        $foreignKey = $dependency['foreign_key'] ?? null;
        if (!$foreignKey) {
            return [
                'mode' => $hasFull ? 'all' : 'selected',
                'selected_ids' => [],
            ];
        }

        $childIdsUnderParent = $childModelClass::query()
            ->where($foreignKey, $parentResource->getKey())
            ->pluck('id')
            ->all();

        if (empty($childIdsUnderParent)) {
            return [
                'mode' => $hasFull ? 'all' : 'selected',
                'selected_ids' => [],
            ];
        }

        $selectedIds = [];
        foreach ($childIdsUnderParent as $childId) {
            $slug = sprintf('%s.%s.%s', $childResourceKey, $action, (string) $childId);
            if (method_exists($user, 'hasAction') && $user->hasAction($slug)) {
                $selectedIds[] = (string) $childId;
            }
        }

        return [
            'mode' => $hasFull ? 'all' : 'selected',
            'selected_ids' => $selectedIds,
        ];
    }

    /**
     * Установить доступ к дочерним ресурсам через родителя:
     * - mode = 'all'  -> выдать право на родителя (наследование на все дочерние, включая будущие)
     * - mode = 'selected' -> снять право на родителя и синхронизировать точечные права на дочерние
     */
    public function setAccessForDependentResource(
        Model $user,
        Model $parentResource,
        string $childResourceKey,
        string $action,
        string $mode,
        string $childModelClass,
        array $selectedChildIds = []
    ): void {
        $this->authorize('admin.users.manage');

        $dependency = config('unperm.resource_dependencies.' . $childResourceKey, []);
        $parentAction = $dependency['actions'][$action] ?? $action;
        $foreignKey = $dependency['foreign_key'] ?? null;

        if ($mode === 'all') {
            ResourcePermission::grant($user, $parentResource, $parentAction);

            if ($foreignKey) {
                $childIds = $childModelClass::query()
                    ->where($foreignKey, $parentResource->getKey())
                    ->pluck('id')
                    ->all();

                foreach ($childIds as $childId) {
                    $child = $childModelClass::find($childId);
                    if ($child) {
                        ResourcePermission::revoke($user, $child, $action);
                    }
                }
            }

            return;
        }

        // selected mode
        ResourcePermission::revoke($user, $parentResource, $parentAction);

        if (!$foreignKey) {
            return;
        }

        $childIds = $childModelClass::query()
            ->where($foreignKey, $parentResource->getKey())
            ->pluck('id')
            ->all();

        $selectedSet = array_map('strval', $selectedChildIds);

        foreach ($childIds as $childId) {
            $child = $childModelClass::find($childId);
            if (!$child) {
                continue;
            }

            if (in_array((string) $childId, $selectedSet, true)) {
                ResourcePermission::grant($user, $child, $action);
            } else {
                ResourcePermission::revoke($user, $child, $action);
            }
        }
    }
}
