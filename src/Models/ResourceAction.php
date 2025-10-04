<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Models;

use DFiks\UnPerm\Traits\HasBitmask;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
            'model_actions',
            'action_id',
            'model_id',
            'id',
            'id'
        );
    }
    
    public function allUsers()
    {
        // Получаем всех пользователей из всех моделей
        $usersByModel = [];
        
        $pivotData = \DB::table('model_actions')
            ->where('action_id', $this->id)
            ->get();
            
        foreach ($pivotData as $pivot) {
            if (class_exists($pivot->model_type)) {
                try {
                    $user = $pivot->model_type::find($pivot->model_id);
                    if ($user) {
                        $usersByModel[] = $user;
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }
        }
        
        return collect($usersByModel);
    }

    public static function findOrCreateForResource($resource, string $actionType): self
    {
        $resourceType = get_class($resource);
        $resourceId = $resource->getKey();
        $slug = sprintf(
            '%s:%s:%s',
            class_basename($resourceType),
            $resourceId,
            $actionType
        );

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

