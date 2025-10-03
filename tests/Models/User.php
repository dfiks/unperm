<?php

namespace DFiks\UnPerm\Tests\Models;

use DFiks\UnPerm\Traits\HasPermissions;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasUuids;
    use HasPermissions;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
    ];
}
