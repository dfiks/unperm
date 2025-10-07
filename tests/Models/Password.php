<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Tests\Models;

use DFiks\UnPerm\Traits\HasResourcePermissions;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Password extends Model
{
    use HasUuids;
    use HasResourcePermissions;

    protected $fillable = ['name', 'secret', 'folder_id'];

    protected $resourcePermissionKey = 'passwords';

    public function folder()
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }
}


