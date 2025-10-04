<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Models;

use DFiks\UnPerm\Traits\HasBitmask;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Throwable;

class ResourceAction extends Model
{
    use HasBitmask;
    use HasUuids;

    protected $fillable = [
        'resource_type',
        'resource_id',
        'action_type',
        'slug',
        'bitmask',
        'description',
    ];

    public function users(): BelongsToMany
    {
        return $this->morphedByMany(
            config('unperm.user_model', 'App\\Models\\User'),
            'model',
            'model_resource_actions',
            'resource_action_id',
            'model_id'
        )->withTimestamps();
    }

    public function allUsers()
    {
        // Получаем все pivot записи через саму связь
        // Используем newPivot() для доступа к pivot данным
        $connection = $this->getConnection();
        $pivots = $this->users()
            ->newPivotStatement()
            ->where('resource_action_id', $this->id)
            ->get(['model_type', 'model_id']);

        // Гидратируем модели пользователей из разных классов
        return $pivots->map(function ($pivot) {
            if (class_exists($pivot->model_type)) {
                try {
                    return $pivot->model_type::find($pivot->model_id);
                } catch (Throwable $e) {
                    return null;
                }
            }

            return null;
        })->filter();
    }

    public static function findOrCreateForResource($resource, string $actionType): self
    {
        $resourceType = get_class($resource);
        $resourceId = $resource->getKey();
        
        // Используем метод getResourcePermissionSlug если доступен
        if (method_exists($resource, 'getResourcePermissionSlug')) {
            $slug = $resource->getResourcePermissionSlug($actionType);
        } else {
            // Иначе создаём slug в стандартном формате
            $slug = sprintf(
                '%s.%s.%s',
                method_exists($resource, 'getResourcePermissionKey') 
                    ? $resource->getResourcePermissionKey() 
                    : $resource->getTable(),
                $actionType,
                $resourceId
            );
        }

        return static::firstOrCreate(
            [
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'action_type' => $actionType,
            ],
            [
                'slug' => $slug,
                'description' => "Action '{$actionType}' on " . class_basename($resourceType) . " #{$resourceId}",
            ]
        );
    }

    public static function getForResource($resource): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('resource_type', get_class($resource))
            ->where('resource_id', $resource->getKey())
            ->get();
    }

    public function getResourceClassName(): string
    {
        return class_basename($this->resource_type);
    }
}
