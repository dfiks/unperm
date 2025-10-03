# Оптимизация Битовых Масок

## Проблема

При большом количестве permissions битовая маска становится огромной:

```php
// 10,000 доступных permissions в системе
// Пользователь имеет только 5 из них: [0, 100, 1000, 5000, 9999]

// Традиционная битовая маска:
$bitmask = "очень длинное число с 3000+ цифрами";
// Размер: 3015 байт
```

## Решение: BitmaskOptimizer

Вместо хранения всей маски храним только **индексы установленных битов**:

```php
use DFiks\UnPerm\Support\BitmaskOptimizer;

// Было: 3015 байт
$bitmask = BitmaskOptimizer::fromIndices([0, 100, 1000, 5000, 9999]);

// Стало: ~35 байт (JSON массив индексов)
$optimized = BitmaskOptimizer::compress($bitmask);
// "[0,100,1000,5000,9999]"

// Экономия: 98.8% !
```

## Когда это выгодно?

### ✅ Выгодно (разреженные данные)

```php
// Сценарий: 10,000 permissions, пользователь имеет 10
$indices = [5, 42, 100, 256, 789, 1024, 2000, 3500, 4500, 9999];
$bitmask = BitmaskOptimizer::fromIndices($indices);

$stats = BitmaskOptimizer::getStats($bitmask);
// bits_set: 10
// total_size: 3015 байт
// compressed_size: 45 байт
// compression_ratio: 98.5% экономии 💚
```

### ❌ Не выгодно (плотные данные)

```php
// Сценарий: 200 permissions, пользователь имеет 150
$indices = range(0, 149); // 150 permissions
$bitmask = BitmaskOptimizer::fromIndices($indices);

$stats = BitmaskOptimizer::getStats($bitmask);
// bits_set: 150
// total_size: 62 байта
// compressed_size: 600+ байт (длинный JSON массив)
// compression_ratio: -870% (стало ХУЖЕ!) ❌
```

## Автоматический выбор формата

```php
use DFiks\UnPerm\Support\BitmaskOptimizer;

// Автоматически выбирает лучший формат
$optimized = BitmaskOptimizer::optimize($bitmask);

if ($optimized['type'] === 'indices') {
    echo "Используем сжатие (экономия места)";
    // data: "[5,42,100,...]"
} else {
    echo "Используем битовую маску (более эффективно)";
    // data: "1234567890..."
}

// Всегда можно восстановить
$original = BitmaskOptimizer::restore($optimized);
```

## Реальный пример использования

```php
use DFiks\UnPerm\Support\BitmaskOptimizer;
use DFiks\UnPerm\Traits\HasPermissions;

class User extends Model
{
    use HasPermissions;
    
    // Поле для хранения оптимизированного формата
    protected $casts = [
        'permissions_meta' => 'array',
    ];
    
    /**
     * Получить битовую маску с автоматической оптимизацией
     */
    public function getOptimizedPermissionBitmask(): string
    {
        $bitmask = $this->getPermissionBitmask();
        
        // Кешируем оптимизированный формат
        if (!$this->permissions_meta) {
            $this->permissions_meta = BitmaskOptimizer::optimize($bitmask);
            $this->save();
        }
        
        return BitmaskOptimizer::restore($this->permissions_meta);
    }
    
    /**
     * Проверить разрешение с оптимизацией
     */
    public function hasOptimizedAction(string $action): bool
    {
        $bitmask = $this->getOptimizedPermissionBitmask();
        return \DFiks\UnPerm\Support\PermBit::hasAction($bitmask, $action);
    }
}
```

## Анализ текущей системы

```bash
php artisan unperm:analyze-bitmask
```

Вывод:
```
Analyzing bitmask storage efficiency...

═══ Actions ═══
+----------------+------------------+-------------+----------+------------+-----------+
| Name           | Slug             | Permissions | Original | Compressed | Savings % |
+----------------+------------------+-------------+----------+------------+-----------+
| Large Action   | large.permission | 1           | 3.2 KB   | 15 B       | 99.5%     |
| Medium Action  | med.permission   | 5           | 1.5 KB   | 35 B       | 97.7%     |
+----------------+------------------+-------------+----------+------------+-----------+

═══════════════════════════════════════════════
             SUMMARY
═══════════════════════════════════════════════

+--------+-------+---------------+-----------------+-----------+
| Type   | Count | Original Size | Compressed Size | Savings % |
+--------+-------+---------------+-----------------+-----------+
| Actions| 25    | 15.2 KB       | 850 B           | 94.4%     |
| Roles  | 10    | 8.5 KB        | 450 B           | 94.7%     |
| Groups | 5     | 4.1 KB        | 200 B           | 95.1%     |
| TOTAL  | 40    | 27.8 KB       | 1.5 KB          | 94.6%     |
+--------+-------+---------------+-----------------+-----------+

💡 Significant savings possible! Consider implementing BitmaskOptimizer.
   For sparse permissions (few assigned), compression can save 95% of storage.
```

## Сравнение подходов

| Подход | Размер (10 из 10k) | Размер (5000 из 10k) | Проверка |
|--------|-------------------|---------------------|----------|
| **Полная маска** | 3015 байт | 3015 байт | O(1) битовая операция |
| **Индексы (Optimizer)** | 45 байт | 30 KB | O(1) после восстановления |
| **Гибридный (auto)** | 45 байт | 3015 байт | O(1) битовая операция |

## Рекомендации

### Малые системы (< 1000 permissions)
- ✅ Используйте обычные битовые маски
- Размер управляемый (~300 байт max)
- Нет необходимости в оптимизации

### Средние системы (1000-5000 permissions)
- ✅ Анализируйте каждый случай
- Используйте `unperm:analyze-bitmask`
- Применяйте оптимизацию для разреженных данных

### Большие системы (> 5000 permissions)
- ✅ Обязательно используйте BitmaskOptimizer
- Экономия может составить 90-99%
- Критично для масштабируемости

## Миграция на оптимизированное хранение

```php
// Добавьте поле в миграцию
Schema::table('users', function (Blueprint $table) {
    $table->json('permissions_optimized')->nullable();
});

// Конвертируйте существующие данные
foreach (User::all() as $user) {
    $bitmask = $user->getPermissionBitmask();
    $user->permissions_optimized = BitmaskOptimizer::optimize($bitmask);
    $user->save();
}
```

## Заключение

**BitmaskOptimizer** решает проблему размера битовых масок для систем с:
- Большим количеством permissions (1000+)
- Разреженным назначением (пользователи имеют < 10% от всех)
- Требованиями к масштабируемости

**Экономия: до 99% места при сохранении O(1) производительности!**

