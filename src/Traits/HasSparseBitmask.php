<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Traits;

use DFiks\UnPerm\Models\PermissionBit;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

/**
 * Трейт для разреженного хранения битовых масок.
 *
 * Вместо хранения поля bitmask, использует полиморфную таблицу permission_bits.
 * Автоматически конвертирует туда-обратно, прозрачно для кода.
 */
trait HasSparseBitmask
{
    /**
     * Включено ли разреженное хранение для этой конкретной записи.
     *
     * Интеллектуальное решение на основе статистики
     */
    public function useSparseBitmask(): bool
    {
        $mode = config('unperm.sparse_storage_mode', 'never');

        switch ($mode) {
            case 'always':
                return true;

            case 'never':
                return false;

            case 'auto':
                return $this->shouldUseSparseBitmaskAuto();

            default:
                return false;
        }
    }

    /**
     * Автоматическое определение - использовать ли sparse storage.
     */
    protected function shouldUseSparseBitmaskAuto(): bool
    {
        if (!$this->exists) {
            return false;
        }

        // Получаем текущую битовую маску
        $bitmask = $this->attributes['bitmask'] ?? '0';

        if ($bitmask === '0') {
            return false;
        }

        $config = config('unperm.sparse_storage_auto', []);
        $minSize = $config['min_bitmask_size'] ?? 100;
        $maxBits = $config['max_bits_for_sparse'] ?? 50;
        $minSavings = $config['min_savings_percent'] ?? 30;

        // 1. Проверяем размер битовой маски
        $bitmaskSize = strlen($bitmask);
        if ($bitmaskSize < $minSize) {
            return false; // Слишком маленькая, не стоит заморачиваться
        }

        // 2. Считаем количество установленных битов
        $positions = $this->bitmaskToPositions($bitmask);
        $bitsCount = count($positions);

        if ($bitsCount > $maxBits) {
            return false; // Слишком много битов, sparse storage не эффективен
        }

        // 3. Оцениваем экономию места
        // Sparse storage: ~100 байт на бит (UUID + позиция + индексы)
        $sparseSize = $bitsCount * 100;
        $savings = (1 - ($sparseSize / $bitmaskSize)) * 100;

        return $savings >= $minSavings;
    }

    /**
     * Связь с битами разрешений.
     */
    public function permissionBits(): MorphMany
    {
        return $this->morphMany(PermissionBit::class, 'model');
    }

    /**
     * Получить битовую маску (из разреженного хранилища или поля с кешированием).
     */
    public function getBitmaskAttribute(): string
    {
        // Если используется разреженное хранение
        if ($this->useSparseBitmask()) {
            return $this->getCachedBitmaskFromBits();
        }

        // Иначе читаем из поля bitmask
        return $this->attributes['bitmask'] ?? '0';
    }

    /**
     * Получить битовую маску с кешированием через Redis.
     */
    protected function getCachedBitmaskFromBits(): string
    {
        if (!$this->exists) {
            return '0';
        }

        // Проверяем включено ли кеширование
        if (!config('unperm.cache.enabled') || !config('unperm.cache.cache_sparse_bits')) {
            return $this->getBitmaskFromBits();
        }

        $cacheKey = $this->getSparseBitmaskCacheKey();
        $ttl = config('unperm.cache.ttl_sparse_bits', 1800);

        return cache()->remember($cacheKey, $ttl, function () {
            return $this->getBitmaskFromBits();
        });
    }

    /**
     * Ключ кеша для битовой маски.
     */
    protected function getSparseBitmaskCacheKey(): string
    {
        $prefix = config('unperm.cache.prefix', 'unperm');
        $type = class_basename($this);

        return "{$prefix}:sparse_bitmask:{$type}:{$this->id}";
    }

    /**
     * Установить битовую маску (в разреженное хранилище или поле).
     */
    public function setBitmaskAttribute(string $value): void
    {
        if ($this->useSparseBitmask()) {
            if ($this->exists) {
                // Если модель уже сохранена, сохраняем в bits
                $this->setBitmaskToBits($value);
            }
            // Для несохраненных моделей просто сохраняем в атрибут bitmask временно
            $this->attributes['bitmask'] = $value;
        } else {
            $this->attributes['bitmask'] = $value;
        }
    }

    /**
     * Получить битовую маску из разреженного хранилища.
     */
    protected function getBitmaskFromBits(): string
    {
        if (!$this->exists) {
            return '0';
        }

        $positions = $this->permissionBits()
            ->orderBy('bit_position')
            ->pluck('bit_position')
            ->toArray();

        if (empty($positions)) {
            return '0';
        }

        return $this->positionsToBitmask($positions);
    }

    /**
     * Сохранить битовую маску в разреженное хранилище.
     */
    protected function setBitmaskToBits(string $bitmask): void
    {
        if (!$this->exists) {
            return;
        }

        $positions = $this->bitmaskToPositions($bitmask);

        // Удаляем старые биты и вставляем новые одной транзакцией
        DB::transaction(function () use ($positions) {
            // Удаляем все существующие биты
            $this->permissionBits()->delete();

            // Вставляем новые биты
            if (!empty($positions)) {
                $data = array_map(fn ($pos) => [
                    'model_type' => get_class($this),
                    'model_id' => $this->id,
                    'bit_position' => $pos,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $positions);

                PermissionBit::insert($data);
            }
        });

        // Очищаем кеш связи
        unset($this->relations['permissionBits']);

        // Очищаем кеш битовой маски
        $this->clearSparseBitmaskCache();
    }

    /**
     * Очистить кеш битовой маски.
     */
    protected function clearSparseBitmaskCache(): void
    {
        if (config('unperm.cache.enabled')) {
            cache()->forget($this->getSparseBitmaskCacheKey());
        }
    }

    /**
     * Конвертировать битовую маску в список позиций.
     */
    protected function bitmaskToPositions(string $bitmask): array
    {
        if ($bitmask === '0') {
            return [];
        }

        $gmp = gmp_init($bitmask);
        $positions = [];
        $position = 0;

        while (gmp_cmp($gmp, 0) > 0) {
            if (gmp_testbit($gmp, 0)) {
                $positions[] = $position;
            }
            $gmp = gmp_div($gmp, 2);
            $position++;
        }

        return $positions;
    }

    /**
     * Конвертировать список позиций в битовую маску.
     */
    protected function positionsToBitmask(array $positions): string
    {
        if (empty($positions)) {
            return '0';
        }

        $result = gmp_init(0);
        foreach ($positions as $position) {
            $result = gmp_or($result, gmp_pow(2, (int) $position));
        }

        return gmp_strval($result);
    }

    /**
     * Обработать битовую маску после сохранения модели.
     */
    protected static function bootHasSparseBitmask(): void
    {
        static::saved(function ($model) {
            if ($model->useSparseBitmask() && isset($model->attributes['bitmask'])) {
                // После сохранения модели, переносим bitmask в разреженное хранилище
                $bitmask = $model->attributes['bitmask'];
                if ($bitmask !== '0' && $bitmask !== null) {
                    $model->setBitmaskToBits($bitmask);
                }
            }
        });
    }

    /**
     * Установить конкретный бит
     */
    public function setSparseBit(int $position): void
    {
        if (!$this->useSparseBitmask() || !$this->exists) {
            return;
        }

        PermissionBit::firstOrCreate([
            'model_type' => get_class($this),
            'model_id' => $this->id,
            'bit_position' => $position,
        ]);

        unset($this->relations['permissionBits']);
    }

    /**
     * Снять конкретный бит
     */
    public function unsetSparseBit(int $position): void
    {
        if (!$this->useSparseBitmask() || !$this->exists) {
            return;
        }

        PermissionBit::where([
            'model_type' => get_class($this),
            'model_id' => $this->id,
            'bit_position' => $position,
        ])->delete();

        unset($this->relations['permissionBits']);
    }

    /**
     * Проверить установлен ли бит (оптимизированная версия).
     */
    public function hasSparseBit(int $position): bool
    {
        if (!$this->useSparseBitmask() || !$this->exists) {
            return false;
        }

        return PermissionBit::where([
            'model_type' => get_class($this),
            'model_id' => $this->id,
            'bit_position' => $position,
        ])->exists();
    }

    /**
     * Получить статистику использования разреженного хранилища.
     */
    public function getSparseBitmaskStats(): array
    {
        if (!$this->useSparseBitmask() || !$this->exists) {
            return [
                'enabled' => false,
                'bits_set' => 0,
                'storage_size' => 0,
            ];
        }

        $bitsCount = $this->permissionBits()->count();

        // Примерный размер: каждая запись ~100 байт (UUID + позиция + timestamps + индексы)
        $storageSize = $bitsCount * 100;

        return [
            'enabled' => true,
            'bits_set' => $bitsCount,
            'storage_size' => $storageSize,
            'storage_size_formatted' => $this->formatBytes($storageSize),
        ];
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        return round($bytes / 1024, 1) . ' KB';
    }
}
