<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Модель для хранения отдельных битов разрешений.
 *
 * Вместо хранения полной битовой маски (которая может быть огромной),
 * храним только позиции установленных битов в отдельной таблице.
 */
class PermissionBit extends Model
{
    protected $fillable = [
        'model_type',
        'model_id',
        'bit_position',
    ];

    protected $casts = [
        'bit_position' => 'integer',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}
