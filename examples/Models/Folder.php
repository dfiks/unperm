<?php

namespace App\Models;

use DFiks\UnPerm\Traits\HasResourcePermissions;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Пример модели Folder с поддержкой UnPerm.
 */
class Folder extends Model
{
    use HasUuids;
    use HasResourcePermissions;

    protected $fillable = [
        'name',
        'description',
        'parent_id',
        'creator_id',
    ];

    protected string $resourcePermissionKey = 'folders';

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Проверить может ли пользователь делиться этой папкой.
     * @param mixed $user
     */
    public function canShare($user): bool
    {
        return $this->userCan($user, 'share')
            || $this->creator_id === $user->id
            || $user->isSuperAdmin();
    }
}
