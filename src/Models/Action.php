<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Models;

use DFiks\UnPerm\Traits\HasBitmask;
use DFiks\UnPerm\Traits\HasSparseBitmask;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Action extends Model
{
    use HasUuids;
    use HasBitmask;
    use HasSparseBitmask;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'bitmask',
    ];

    protected $casts = [
        'bitmask' => 'string',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'roles_action',
            'action_id',
            'role_id'
        )->withTimestamps();
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(
            Group::class,
            'groups_action',
            'action_id',
            'group_id'
        )->withTimestamps();
    }
}
