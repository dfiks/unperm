<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Models;

use DFiks\UnPerm\Traits\HasBitmask;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    use HasUuids;
    use HasBitmask;

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
            'groups_action',
            'group_id',
            'action_id'
        )->withTimestamps();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'groups_roles',
            'group_id',
            'role_id'
        )->withTimestamps();
    }

    public function syncBitmaskFromRolesAndActions(): self
    {
        $bitmask = gmp_init('0');

        foreach ($this->roles as $role) {
            $roleMask = gmp_init($role->bitmask);
            $bitmask = gmp_or($bitmask, $roleMask);
        }

        foreach ($this->actions as $action) {
            $actionMask = gmp_init($action->bitmask);
            $bitmask = gmp_or($bitmask, $actionMask);
        }

        $this->bitmask = gmp_strval($bitmask);

        return $this;
    }
}
