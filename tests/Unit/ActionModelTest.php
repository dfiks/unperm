<?php

namespace DFiks\UnPerm\Tests\Unit;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Tests\TestCase;

class ActionModelTest extends TestCase
{
    public function testCreatesActionWithUuid(): void
    {
        $action = Action::create([
            'name' => 'Test Action',
            'slug' => 'test-action',
            'bitmask' => '1',
        ]);

        $this->assertIsString($action->id);
        $this->assertDatabaseHas('actions', ['slug' => 'test-action']);
    }

    public function testChecksBitInBitmask(): void
    {
        $action = Action::create([
            'name' => 'Test',
            'slug' => 'test',
            'bitmask' => '5',
        ]);

        $this->assertTrue($action->hasBit(0));
        $this->assertFalse($action->hasBit(1));
        $this->assertTrue($action->hasBit(2));
    }

    public function testSetsAndUnsetsBits(): void
    {
        $action = Action::create([
            'name' => 'Test',
            'slug' => 'test',
            'bitmask' => '0',
        ]);

        $action->setBit(3)->save();
        $this->assertEquals('8', $action->bitmask);

        $action->unsetBit(3)->save();
        $this->assertEquals('0', $action->bitmask);
    }

    public function testTogglesBit(): void
    {
        $action = Action::create([
            'name' => 'Test',
            'slug' => 'test',
            'bitmask' => '0',
        ]);

        $action->toggleBit(1)->save();
        $this->assertEquals('2', $action->bitmask);

        $action->toggleBit(1)->save();
        $this->assertEquals('0', $action->bitmask);
    }

    public function testClearsBitmask(): void
    {
        $action = Action::create([
            'name' => 'Test',
            'slug' => 'test',
            'bitmask' => '255',
        ]);

        $action->clearBitmask()->save();
        
        $this->assertEquals('0', $action->bitmask);
    }
}

