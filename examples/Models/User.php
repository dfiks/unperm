<?php

namespace App\Models;

use DFiks\UnPerm\Traits\HasPermissions;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Schema;

/**
 * Пример модели User с поддержкой UnPerm.
 */
class User extends Authenticatable
{
    use HasUuids;
    use Notifiable;
    use HasPermissions;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Проверить является ли пользователь владельцем ресурса.
     * @param mixed $resource
     */
    public function owns($resource): bool
    {
        if (!$resource) {
            return false;
        }

        if (method_exists($resource, 'getOwnerId')) {
            return $resource->getOwnerId() === $this->id;
        }

        if (property_exists($resource, 'creator_id')) {
            return $resource->creator_id === $this->id;
        }

        if (property_exists($resource, 'author_id')) {
            return $resource->author_id === $this->id;
        }

        if (property_exists($resource, 'user_id')) {
            return $resource->user_id === $this->id;
        }

        return false;
    }

    /**
     * Получить все ресурсы принадлежащие пользователю.
     */
    public function ownedResources(string $modelClass)
    {
        $instance = new $modelClass();

        $ownerFields = ['creator_id', 'author_id', 'user_id', 'owner_id'];

        foreach ($ownerFields as $field) {
            if (Schema::hasColumn($instance->getTable(), $field)) {
                return $modelClass::where($field, $this->id);
            }
        }

        return $modelClass::query()->whereRaw('1 = 0');
    }
}
