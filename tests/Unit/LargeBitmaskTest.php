<?php

namespace DFiks\UnPerm\Tests\Unit;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Tests\TestCase;

class LargeBitmaskTest extends TestCase
{
    public function testHandlesLargeBitmasks(): void
    {
        // Создаем action с большой битовой маской (например, 1000-й permission)
        $action = Action::create([
            'name' => 'Large Permission',
            'slug' => 'large.permission',
            'bitmask' => gmp_strval(gmp_pow(2, 999)), // 2^999
        ]);

        // Проверяем что маска сохранилась
        $this->assertNotEquals('0', $action->bitmask);

        // Проверяем длину маски (должна быть ~300 цифр)
        $this->assertGreaterThan(250, strlen($action->bitmask));

        // Проверяем что можем прочитать обратно
        $action = $action->fresh();
        $this->assertGreaterThan(250, strlen($action->bitmask));
    }

    public function testHandles10000Permissions(): void
    {
        // Симулируем 10,000-й permission
        $bitmask = gmp_strval(gmp_pow(2, 9999)); // 2^9999

        $action = Action::create([
            'name' => 'Very Large Permission',
            'slug' => 'very.large.permission',
            'bitmask' => $bitmask,
        ]);

        // Проверяем что можем сохранить и прочитать
        $this->assertNotEquals('0', $action->bitmask);
        $this->assertEquals($bitmask, $action->bitmask);

        // Длина должна быть примерно 3000+ символов
        $this->assertGreaterThan(3000, strlen($action->bitmask));
    }

    public function testCombinesLargeBitmasks(): void
    {
        // Создаем несколько permissions с большими индексами
        $action1 = Action::create([
            'slug' => 'perm.1000',
            'name' => 'Permission 1000',
            'bitmask' => gmp_strval(gmp_pow(2, 999)),
        ]);

        $action2 = Action::create([
            'slug' => 'perm.2000',
            'name' => 'Permission 2000',
            'bitmask' => gmp_strval(gmp_pow(2, 1999)),
        ]);

        // Комбинируем маски через OR
        $combined = gmp_strval(
            gmp_or(
                gmp_init($action1->bitmask),
                gmp_init($action2->bitmask)
            )
        );

        // Проверяем что обе маски присутствуют
        $combinedGmp = gmp_init($combined);
        $mask1 = gmp_init($action1->bitmask);
        $mask2 = gmp_init($action2->bitmask);

        $this->assertEquals(0, gmp_cmp(gmp_and($combinedGmp, $mask1), $mask1));
        $this->assertEquals(0, gmp_cmp(gmp_and($combinedGmp, $mask2), $mask2));
    }

    public function testTextFieldCapacity(): void
    {
        // TEXT поле в MySQL/PostgreSQL поддерживает до 65,535 байт
        // Это позволяет хранить числа с ~65,000 цифр
        // 2^N имеет примерно N * 0.301 десятичных цифр
        // Поэтому мы можем хранить до ~216,000 permissions (65000 / 0.301)

        // Тестируем 20,000 permissions (должно влезть в TEXT)
        $bitmask = gmp_strval(gmp_pow(2, 19999));

        $action = Action::create([
            'slug' => 'massive.permission',
            'name' => 'Massive Permission',
            'bitmask' => $bitmask,
        ]);

        $length = strlen($bitmask);

        // Проверяем что влезло (должно быть ~6000 символов)
        $this->assertLessThan(65000, $length); // Влезает в TEXT
        $this->assertGreaterThan(5000, $length); // Достаточно большое

        // Проверяем что корректно сохранилось
        $action = $action->fresh();
        $this->assertEquals($bitmask, $action->bitmask);
    }
}
