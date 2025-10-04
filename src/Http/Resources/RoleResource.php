<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'bitmask' => $this->bitmask,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'actions' => ActionResource::collection($this->whenLoaded('actions')),
            'resource_actions' => ResourceActionResource::collection($this->whenLoaded('resourceActions')),
            'users_count' => $this->when(isset($this->users_count), $this->users_count ?? 0),
        ];
    }
}
