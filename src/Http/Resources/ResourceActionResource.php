<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ResourceActionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'resource_type' => $this->resource_type,
            'resource_id' => $this->resource_id,
            'action_type' => $this->action_type,
            'slug' => $this->slug,
            'bitmask' => $this->bitmask,
            'description' => $this->description,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'users_count' => $this->when(isset($this->users_count), $this->users_count ?? 0),
            'resource_class_name' => $this->when(method_exists($this->resource, 'getResourceClassName'), fn () => $this->getResourceClassName()),
        ];
    }
}
