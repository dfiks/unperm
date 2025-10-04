<?php

namespace App\Models;

use DFiks\UnPerm\Traits\HasResourcePermissions;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Пример модели Document с поддержкой UnPerm.
 */
class Document extends Model
{
    use HasUuids;
    use HasResourcePermissions;

    protected $fillable = [
        'title',
        'content',
        'folder_id',
        'author_id',
        'file_path',
    ];

    protected string $resourcePermissionKey = 'documents';

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Унаследовать права от папки при создании.
     */
    protected static function booted()
    {
        static::created(function (Document $document) {
            if ($document->folder_id && $document->author) {
                grantResourcePermission(
                    $document->author,
                    $document,
                    'view'
                );
                grantResourcePermission(
                    $document->author,
                    $document,
                    'update'
                );
                grantResourcePermission(
                    $document->author,
                    $document,
                    'delete'
                );
            }
        });
    }
}
