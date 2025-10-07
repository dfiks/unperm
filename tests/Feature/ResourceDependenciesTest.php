<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Tests\Feature;

use DFiks\UnPerm\Support\ResourcePermission;
use DFiks\UnPerm\Tests\Models\Folder;
use DFiks\UnPerm\Tests\Models\Password;
use DFiks\UnPerm\Tests\Models\User;
use DFiks\UnPerm\Tests\TestCase;

class ResourceDependenciesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['unperm.cache.enabled' => false]);
        config([
            'unperm.resource_dependencies' => [
                'passwords' => [
                    'parent' => 'folders',
                    'via' => 'folder',
                    'foreign_key' => 'folder_id',
                    'actions' => [
                        'view' => 'view',
                        'edit' => 'edit',
                        'delete' => 'delete',
                    ],
                ],
            ],
        ]);

        $this->artisan('unperm:sync')->assertSuccessful();
    }

    public function testChildResourceInheritsPermissionFromParent(): void
    {
        $user = User::create(['name' => 'X', 'email' => 'x@example.com']);
        $folder = Folder::create(['name' => 'F']);
        $password = Password::create(['name' => 'P', 'secret' => 's', 'folder_id' => $folder->id]);

        ResourcePermission::grant($user, $folder, 'view');

        $this->assertTrue($password->userCan($user, 'view'));
    }

    public function testScopeIncludesByParentPermission(): void
    {
        $user = User::create(['name' => 'X', 'email' => 'x2@example.com']);
        $folder1 = Folder::create(['name' => 'F1']);
        $folder2 = Folder::create(['name' => 'F2']);

        $p1 = Password::create(['name' => 'P1', 'secret' => 's1', 'folder_id' => $folder1->id]);
        $p2 = Password::create(['name' => 'P2', 'secret' => 's2', 'folder_id' => $folder1->id]);
        $p3 = Password::create(['name' => 'P3', 'secret' => 's3', 'folder_id' => $folder2->id]);

        ResourcePermission::grant($user, $folder1, 'view');

        $viewable = Password::viewableBy($user)->get();

        $this->assertTrue($viewable->contains('id', $p1->id));
        $this->assertTrue($viewable->contains('id', $p2->id));
        $this->assertFalse($viewable->contains('id', $p3->id));
    }

    public function testDirectPermissionStillWorks(): void
    {
        $user = User::create(['name' => 'Y', 'email' => 'y@example.com']);
        $folder = Folder::create(['name' => 'F']);
        $password = Password::create(['name' => 'P', 'secret' => 's', 'folder_id' => $folder->id]);

        ResourcePermission::grant($user, $password, 'view');

        $this->assertTrue($password->userCan($user, 'view'));
    }
}
