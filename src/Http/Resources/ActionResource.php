<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'description' => $this->description,
            'bitmask' => $this->bitmask,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'users_count' => $this->when(isset($this->users_count), $this->users_count ?? 0),
            'roles_count' => $this->when(isset($this->roles_count), $this->roles_count ?? 0),
        ];
    }
}
