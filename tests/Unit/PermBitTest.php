<?php

namespace DFiks\UnPerm\Tests\Unit;

use DFiks\UnPerm\Support\PermBit;
use DFiks\UnPerm\Tests\TestCase;

class PermBitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'unperm.actions' => [
                'users' => [
                    'view' => 'View users',
                    'create' => 'Create users',
                    'edit' => 'Edit users',
                ],
                'posts' => [
                    'view' => 'View posts',
                    'create' => 'Create posts',
                ],
            ],
        ]);

        PermBit::clearCache();
    }

    public function testBuildsActionsFromConfig(): void
    {
        $actions = PermBit::rebuild();

        $this->assertCount(5, $actions);
        $this->assertArrayHasKey('users.view', $actions);
        $this->assertEquals(0, $actions['users.view']['bit_position']);
        $this->assertEquals('1', $actions['users.view']['bitmask']);
    }

    public function testGetsBitmaskForAction(): void
    {
        $mask = PermBit::getBitmask('users.create');

        $this->assertEquals('2', $mask);
    }

    public function testGetsBitPosition(): void
    {
        $position = PermBit::getBitPosition('users.edit');

        $this->assertEquals(2, $position);
    }

    public function testCombinesMultipleActions(): void
    {
        $combined = PermBit::combine(['users.view', 'users.edit']);

        $this->assertEquals('5', $combined);
    }

    public function testChecksIfBitmaskHasAction(): void
    {
        $mask = '7';

        $this->assertTrue(PermBit::hasAction($mask, 'users.view'));
        $this->assertTrue(PermBit::hasAction($mask, 'users.create'));
        $this->assertTrue(PermBit::hasAction($mask, 'users.edit'));
        $this->assertFalse(PermBit::hasAction($mask, 'posts.view'));
    }

    public function testChecksAllActions(): void
    {
        $mask = '7';

        $this->assertTrue(PermBit::hasAllActions($mask, ['users.view', 'users.create']));
        $this->assertFalse(PermBit::hasAllActions($mask, ['users.view', 'posts.view']));
    }

    public function testChecksAnyAction(): void
    {
        $mask = '2';

        $this->assertTrue(PermBit::hasAnyAction($mask, ['users.create', 'posts.create']));
        $this->assertFalse(PermBit::hasAnyAction($mask, ['users.view', 'posts.view']));
    }

    public function testAddsActionToMask(): void
    {
        $mask = '0';

        $mask = PermBit::addAction($mask, 'users.create');

        $this->assertEquals('2', $mask);
        $this->assertTrue(PermBit::hasAction($mask, 'users.create'));
    }

    public function testRemovesActionFromMask(): void
    {
        $mask = '7';

        $mask = PermBit::removeAction($mask, 'users.create');

        $this->assertEquals('5', $mask);
        $this->assertFalse(PermBit::hasAction($mask, 'users.create'));
    }

    public function testGetsActionsFromMask(): void
    {
        $mask = '7';

        $actions = PermBit::getActions($mask);

        $this->assertCount(3, $actions);
        $this->assertContains('users.view', $actions);
        $this->assertContains('users.create', $actions);
        $this->assertContains('users.edit', $actions);
    }

    public function testConvertsToHex(): void
    {
        $mask = '255';

        $hex = PermBit::toHex($mask);

        $this->assertEquals('ff', $hex);
    }

    public function testConvertsToBinary(): void
    {
        $mask = '7';

        $binary = PermBit::toBinary($mask);

        $this->assertEquals('111', $binary);
    }

    public function testHandlesLargeNumbers(): void
    {
        $mask = gmp_strval(gmp_pow(2, 100));

        $this->assertTrue(PermBit::hasBit($mask, 100));
        $this->assertFalse(PermBit::hasBit($mask, 99));
    }
}
