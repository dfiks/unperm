<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Models;

use DFiks\UnPerm\Traits\HasBitmask;
use DFiks\UnPerm\Traits\HasSparseBitmask;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
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

    public function actions(): BelongsToMany
    {
        return $this->belongsToMany(
            Action::class,
            'roles_action',
            'role_id',
            'action_id'
        )->withTimestamps();
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(
            Group::class,
            'groups_roles',
            'role_id',
            'group_id'
        )->withTimestamps();
    }

    public function syncBitmaskFromActions(): self
    {
        $bitmask = gmp_init('0');
        foreach ($this->actions as $action) {
            $actionMask = gmp_init($action->bitmask);
            $bitmask = gmp_or($bitmask, $actionMask);
        }
        $this->bitmask = gmp_strval($bitmask);

        return $this;
    }
}
