<?php

namespace DFiks\UnPerm\Tests\Unit;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\PermissionBit;
use DFiks\UnPerm\Models\Role;
use DFiks\UnPerm\Tests\TestCase;

class SparseBitmaskTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Включаем принудительное разреженное хранение для тестов
        config(['unperm.sparse_storage_mode' => 'always']);
    }

    public function testStoresBitmaskAsSeparateBits(): void
    {
        $action = Action::create([
            'name' => 'Test Action',
            'slug' => 'test.action',
            'bitmask' => '5', // binary: 101 (биты 0 и 2)
        ]);

        // Проверяем что биты сохранились в отдельной таблице
        $bits = PermissionBit::where('model_type', Action::class)
            ->where('model_id', $action->id)
            ->pluck('bit_position')
            ->toArray();

        $this->assertEquals([0, 2], $bits);
    }

    public function testReadsBitmaskFromSeparateBits(): void
    {
        $action = Action::create([
            'name' => 'Test Action',
            'slug' => 'test.action',
            'bitmask' => '5',
        ]);

        // Перезагружаем из БД
        $action = $action->fresh();

        // Должен восстановить битовую маску из отдельных битов
        $this->assertEquals('5', $action->bitmask);
    }

    public function testHandlesLargeSparseBitmask(): void
    {
        // Создаем action с огромной разреженной маской
        // Биты: 0, 1000, 5000 (всего 3 бита из 5000+)
        $positions = [0, 1000, 5000];
        $bitmask = $this->positionsToBitmask($positions);

        $action = Action::create([
            'name' => 'Large Sparse',
            'slug' => 'large.sparse',
            'bitmask' => $bitmask,
        ]);

        // Проверяем что сохранилось только 3 записи вместо огромной строки
        $bitsCount = PermissionBit::where('model_type', Action::class)
            ->where('model_id', $action->id)
            ->count();

        $this->assertEquals(3, $bitsCount);

        // Проверяем что можем восстановить
        $action = $action->fresh();
        $this->assertEquals($bitmask, $action->bitmask);
    }

    public function testSetsIndividualBit(): void
    {
        $action = Action::create([
            'name' => 'Test',
            'slug' => 'test',
            'bitmask' => '0',
        ]);

        $action->setSparseBit(42);

        // Проверяем что бит установлен
        $this->assertTrue($action->hasSparseBit(42));

        // Проверяем что битовая маска обновилась
        $action = $action->fresh();
        $this->assertEquals(gmp_strval(gmp_pow(2, 42)), $action->bitmask);
    }

    public function testUnsetsIndividualBit(): void
    {
        $action = Action::create([
            'name' => 'Test',
            'slug' => 'test',
            'bitmask' => '5', // биты 0 и 2
        ]);

        $action->unsetSparseBit(0);

        // Проверяем что бит снят
        $this->assertFalse($action->hasSparseBit(0));
        $this->assertTrue($action->hasSparseBit(2));

        // Проверяем что битовая маска обновилась
        $action = $action->fresh();
        $this->assertEquals('4', $action->bitmask); // только бит 2
    }

    public function testGetStats(): void
    {
        $action = Action::create([
            'name' => 'Test',
            'slug' => 'test',
            'bitmask' => '7', // биты 0, 1, 2
        ]);

        $stats = $action->getSparseBitmaskStats();

        $this->assertTrue($stats['enabled']);
        $this->assertEquals(3, $stats['bits_set']);
        $this->assertEquals(300, $stats['storage_size']); // 3 * 100 байт
    }

    public function testWorksWithRolesAndGroups(): void
    {
        $role = Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
            'bitmask' => $this->positionsToBitmask([0, 100, 1000]),
        ]);

        // Проверяем что биты сохранились
        $bitsCount = PermissionBit::where('model_type', Role::class)
            ->where('model_id', $role->id)
            ->count();

        $this->assertEquals(3, $bitsCount);

        // Проверяем восстановление
        $role = $role->fresh();
        $expected = $this->positionsToBitmask([0, 100, 1000]);
        $this->assertEquals($expected, $role->bitmask);
    }

    public function testDisabledByDefault(): void
    {
        config(['unperm.sparse_storage_mode' => 'never']);

        $action = Action::create([
            'name' => 'Test',
            'slug' => 'test',
            'bitmask' => '5',
        ]);

        // Не должно быть записей в permission_bits
        $bitsCount = PermissionBit::where('model_type', Action::class)
            ->where('model_id', $action->id)
            ->count();

        $this->assertEquals(0, $bitsCount);

        // Битовая маска должна храниться в обычном поле
        $action = $action->fresh();
        $this->assertEquals('5', $action->bitmask);
    }

    public function testAutoModeChoosesOptimal(): void
    {
        config(['unperm.sparse_storage_mode' => 'auto']);

        // Маленькая маска - не должна использовать sparse
        $small = Action::create([
            'name' => 'Small',
            'slug' => 'small',
            'bitmask' => '5', // ~1 байт
        ]);

        $this->assertFalse($small->useSparseBitmask());
        $this->assertEquals(0, PermissionBit::where('model_id', $small->id)->count());

        // Большая разреженная маска - должна использовать sparse
        $large = Action::create([
            'name' => 'Large',
            'slug' => 'large',
            'bitmask' => $this->positionsToBitmask([0, 1000, 5000]), // ~1500 байт
        ]);

        // После первого сохранения sparse storage может быть еще не активирован
        // Перезагружаем и проверяем
        $large = $large->fresh();

        // Должен использовать sparse storage (большая маска, мало битов)
        $this->assertTrue($large->useSparseBitmask());
    }

    protected function positionsToBitmask(array $positions): string
    {
        $result = gmp_init(0);
        foreach ($positions as $position) {
            $result = gmp_or($result, gmp_pow(2, $position));
        }

        return gmp_strval($result);
    }
}
