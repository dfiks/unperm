<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Support;

/**
 * Оптимизатор битовых масок.
 *
 * Предоставляет сжатое хранение битовых масок через индексы
 * вместо полной битовой маски для разреженных данных.
 */
class BitmaskOptimizer
{
    /**
     * Конвертировать битовую маску в список индексов.
     *
     * Пример: "5" (binary: 101) -> [0, 2]
     *
     * @param  string     $bitmask Битовая маска в виде строки
     * @return array<int> Массив индексов установленных битов
     */
    public static function toIndices(string $bitmask): array
    {
        if ($bitmask === '0') {
            return [];
        }

        $gmp = gmp_init($bitmask);
        $indices = [];
        $position = 0;

        // Проходим по всем битам
        while (gmp_cmp($gmp, 0) > 0) {
            // Если младший бит установлен
            if (gmp_testbit($gmp, 0)) {
                $indices[] = $position;
            }

            // Сдвигаем вправо на 1 бит
            $gmp = gmp_div($gmp, 2);
            $position++;
        }

        return $indices;
    }

    /**
     * Конвертировать список индексов обратно в битовую маску.
     *
     * Пример: [0, 2] -> "5" (binary: 101)
     *
     * @param  array<int> $indices Массив индексов
     * @return string     Битовая маска
     */
    public static function fromIndices(array $indices): string
    {
        if (empty($indices)) {
            return '0';
        }

        $result = gmp_init(0);

        foreach ($indices as $index) {
            $result = gmp_or($result, gmp_pow(2, $index));
        }

        return gmp_strval($result);
    }

    /**
     * Сжать битовую маску в JSON строку индексов.
     *
     * Эффективно для разреженных масок (мало установленных битов)
     *
     * @param  string $bitmask
     * @return string JSON массив индексов
     */
    public static function compress(string $bitmask): string
    {
        $indices = self::toIndices($bitmask);

        return json_encode($indices, JSON_THROW_ON_ERROR);
    }

    /**
     * Распаковать JSON индексы обратно в битовую маску.
     *
     * @param  string $compressed JSON массив индексов
     * @return string Битовая маска
     */
    public static function decompress(string $compressed): string
    {
        $indices = json_decode($compressed, true, 512, JSON_THROW_ON_ERROR);

        return self::fromIndices($indices);
    }

    /**
     * Определить, выгодно ли сжатие.
     *
     * Сравниваем размер битовой маски и JSON массива индексов
     *
     * @param  string $bitmask
     * @return bool   True если сжатие уменьшит размер
     */
    public static function shouldCompress(string $bitmask): bool
    {
        if ($bitmask === '0') {
            return false;
        }

        $originalSize = strlen($bitmask);
        $compressed = self::compress($bitmask);
        $compressedSize = strlen($compressed);

        // Сжимаем если экономия более 20%
        return $compressedSize < ($originalSize * 0.8);
    }

    /**
     * Получить размер экономии в процентах.
     *
     * @param  string $bitmask
     * @return float  Процент экономии (0-100)
     */
    public static function getCompressionRatio(string $bitmask): float
    {
        if ($bitmask === '0') {
            return 0.0;
        }

        $originalSize = strlen($bitmask);
        $compressedSize = strlen(self::compress($bitmask));

        if ($originalSize === 0) {
            return 0.0;
        }

        return (1 - ($compressedSize / $originalSize)) * 100;
    }

    /**
     * Оптимальное хранение: автоматически выбирает формат
     *
     * Возвращает массив с типом и данными
     *
     * @param  string                            $bitmask
     * @return array{type: string, data: string} ['type' => 'bitmask'|'indices', 'data' => string]
     */
    public static function optimize(string $bitmask): array
    {
        if (self::shouldCompress($bitmask)) {
            return [
                'type' => 'indices',
                'data' => self::compress($bitmask),
            ];
        }

        return [
            'type' => 'bitmask',
            'data' => $bitmask,
        ];
    }

    /**
     * Восстановить битовую маску из оптимизированного формата.
     *
     * @param  array{type: string, data: string} $optimized
     * @return string                            Битовая маска
     */
    public static function restore(array $optimized): string
    {
        if ($optimized['type'] === 'indices') {
            return self::decompress($optimized['data']);
        }

        return $optimized['data'];
    }

    /**
     * Статистика по битовой маске.
     *
     * @param  string                                                                                $bitmask
     * @return array{bits_set: int, total_size: int, compressed_size: int, compression_ratio: float}
     */
    public static function getStats(string $bitmask): array
    {
        $indices = self::toIndices($bitmask);

        return [
            'bits_set' => count($indices),
            'total_size' => strlen($bitmask),
            'compressed_size' => strlen(self::compress($bitmask)),
            'compression_ratio' => self::getCompressionRatio($bitmask),
        ];
    }
}
