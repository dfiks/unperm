<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Tests\Models;

use DFiks\UnPerm\Traits\HasResourcePermissions;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    use HasUuids;
    use HasResourcePermissions;

    protected $fillable = ['name', 'description'];

    protected $resourcePermissionKey = 'folders';
}
