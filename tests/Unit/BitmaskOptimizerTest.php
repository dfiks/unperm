<?php

namespace DFiks\UnPerm\Tests\Unit;

use DFiks\UnPerm\Support\BitmaskOptimizer;
use DFiks\UnPerm\Tests\TestCase;

class BitmaskOptimizerTest extends TestCase
{
    public function testConvertsToIndices(): void
    {
        // 5 в бинарном виде: 101 (биты 0 и 2 установлены)
        $indices = BitmaskOptimizer::toIndices('5');

        $this->assertEquals([0, 2], $indices);
    }

    public function testConvertsFromIndices(): void
    {
        $bitmask = BitmaskOptimizer::fromIndices([0, 2]);

        $this->assertEquals('5', $bitmask);
    }

    public function testRoundTripConversion(): void
    {
        $original = '12345';

        $indices = BitmaskOptimizer::toIndices($original);
        $restored = BitmaskOptimizer::fromIndices($indices);

        $this->assertEquals($original, $restored);
    }

    public function testCompressesSparseBitmask(): void
    {
        // Разреженная маска: только биты 0, 100, 1000 установлены
        $bitmask = BitmaskOptimizer::fromIndices([0, 100, 1000]);

        $compressed = BitmaskOptimizer::compress($bitmask);
        $decompressed = BitmaskOptimizer::decompress($compressed);

        $this->assertEquals($bitmask, $decompressed);

        // Сжатая версия должна быть намного меньше
        $this->assertLessThan(strlen($bitmask), strlen($compressed));
    }

    public function testCompressesLargeSparsePermissions(): void
    {
        // Пользователь имеет только 5 permissions из 10,000
        $indices = [0, 100, 1000, 5000, 9999];
        $bitmask = BitmaskOptimizer::fromIndices($indices);

        // Оригинальная маска огромная (2^9999)
        $originalSize = strlen($bitmask);
        $this->assertGreaterThan(3000, $originalSize);

        // Сжатая версия - только JSON массив индексов
        $compressed = BitmaskOptimizer::compress($bitmask);
        $compressedSize = strlen($compressed);

        // Должна быть намного меньше (примерно 30-40 байт)
        $this->assertLessThan(100, $compressedSize);

        // Экономия > 99%
        $ratio = BitmaskOptimizer::getCompressionRatio($bitmask);
        $this->assertGreaterThan(99, $ratio);
    }

    public function testShouldCompressSparseData(): void
    {
        // Разреженные данные - выгодно сжимать
        $sparse = BitmaskOptimizer::fromIndices([0, 1000, 5000]);
        $this->assertTrue(BitmaskOptimizer::shouldCompress($sparse));

        // Плотные данные - не выгодно
        $dense = BitmaskOptimizer::fromIndices(range(0, 100));
        $this->assertFalse(BitmaskOptimizer::shouldCompress($dense));
    }

    public function testOptimizeChoosesBestFormat(): void
    {
        // Разреженные данные -> indices
        $sparse = BitmaskOptimizer::fromIndices([0, 5000]);
        $optimized = BitmaskOptimizer::optimize($sparse);
        $this->assertEquals('indices', $optimized['type']);

        // Плотные данные -> bitmask
        $dense = BitmaskOptimizer::fromIndices(range(0, 50));
        $optimized = BitmaskOptimizer::optimize($dense);
        $this->assertEquals('bitmask', $optimized['type']);
    }

    public function testRestoresFromOptimizedFormat(): void
    {
        $original = BitmaskOptimizer::fromIndices([0, 100, 1000]);

        $optimized = BitmaskOptimizer::optimize($original);
        $restored = BitmaskOptimizer::restore($optimized);

        $this->assertEquals($original, $restored);
    }

    public function testGetStats(): void
    {
        $bitmask = BitmaskOptimizer::fromIndices([0, 100, 1000]);
        $stats = BitmaskOptimizer::getStats($bitmask);

        $this->assertEquals(3, $stats['bits_set']);
        $this->assertGreaterThan(0, $stats['total_size']);
        $this->assertGreaterThan(0, $stats['compressed_size']);
        $this->assertGreaterThan(0, $stats['compression_ratio']);
    }

    public function testHandlesEmptyBitmask(): void
    {
        $indices = BitmaskOptimizer::toIndices('0');
        $this->assertEquals([], $indices);

        $bitmask = BitmaskOptimizer::fromIndices([]);
        $this->assertEquals('0', $bitmask);
    }

    public function testRealWorldScenario(): void
    {
        // Реальный сценарий: у пользователя 10 permissions из 5000 доступных
        $userPermissions = [5, 42, 100, 256, 789, 1024, 2000, 3500, 4500, 4999];
        $bitmask = BitmaskOptimizer::fromIndices($userPermissions);

        // Оригинальный размер
        $originalSize = strlen($bitmask);
        $this->assertGreaterThan(1000, $originalSize); // Очень большой

        // Оптимизированный формат
        $optimized = BitmaskOptimizer::optimize($bitmask);
        $optimizedSize = strlen($optimized['data']);

        // Экономия должна быть значительной
        $this->assertLessThan($originalSize / 10, $optimizedSize);

        // Проверяем что данные корректны
        $restored = BitmaskOptimizer::restore($optimized);
        $restoredIndices = BitmaskOptimizer::toIndices($restored);

        $this->assertEquals($userPermissions, $restoredIndices);
    }
}
