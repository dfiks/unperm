<?php

namespace DFiks\UnPerm\Tests\Feature;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Group;
use DFiks\UnPerm\Models\Role;
use DFiks\UnPerm\Tests\TestCase;
use Illuminate\Support\Facades\File;

class GenerateIdeHelperCommandTest extends TestCase
{
    protected string $outputFile;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->outputFile = sys_get_temp_dir() . '/test_ide_helper_permissions.php';
        
        // Синхронизируем тестовые данные
        config([
            'unperm.actions' => [
                'users' => [
                    'view' => 'View users',
                    'create' => 'Create users',
                ],
                'posts' => [
                    'view' => 'View posts',
                    'edit' => 'Edit posts',
                ],
            ],
            'unperm.roles' => [
                'admin' => [
                    'name' => 'Administrator',
                    'actions' => ['users.*', 'posts.*'],
                ],
            ],
            'unperm.groups' => [
                'content-team' => [
                    'name' => 'Content Team',
                    'roles' => ['admin'],
                ],
            ],
        ]);
        
        $this->artisan('unperm:sync');
    }

    protected function tearDown(): void
    {
        if (File::exists($this->outputFile)) {
            File::delete($this->outputFile);
        }
        
        parent::tearDown();
    }

    public function testGeneratesIdeHelperFile(): void
    {
        $this->artisan('unperm:generate-ide-helper', [
            '--output' => $this->outputFile,
        ])->assertSuccessful();

        $this->assertFileExists($this->outputFile);
    }

    public function testGeneratedFileIsValidPhp(): void
    {
        $this->artisan('unperm:generate-ide-helper', [
            '--output' => $this->outputFile,
        ]);

        $content = File::get($this->outputFile);
        
        // Проверяем что файл начинается с <?php
        $this->assertStringStartsWith('<?php', $content);
        
        // Проверяем что файл валиден синтаксически
        $this->assertTrue(
            @eval('?>' . $content) !== false || error_get_last() === null
        );
    }

    public function testIncludesAllActions(): void
    {
        $this->artisan('unperm:generate-ide-helper', [
            '--output' => $this->outputFile,
        ]);

        $content = File::get($this->outputFile);
        
        // Проверяем что все actions есть в файле
        $this->assertStringContainsString('assignAction_users_view', $content);
        $this->assertStringContainsString('hasAction_users_create', $content);
        $this->assertStringContainsString('removeAction_posts_view', $content);
    }

    public function testIncludesAllRoles(): void
    {
        $this->artisan('unperm:generate-ide-helper', [
            '--output' => $this->outputFile,
        ]);

        $content = File::get($this->outputFile);
        
        $this->assertStringContainsString('assignRole_admin', $content);
        $this->assertStringContainsString('hasRole_admin', $content);
        $this->assertStringContainsString('removeRole_admin', $content);
    }

    public function testIncludesAllGroups(): void
    {
        $this->artisan('unperm:generate-ide-helper', [
            '--output' => $this->outputFile,
        ]);

        $content = File::get($this->outputFile);
        
        $this->assertStringContainsString('assignGroup_content_team', $content);
        $this->assertStringContainsString('hasGroup_content_team', $content);
        $this->assertStringContainsString('removeGroup_content_team', $content);
    }

    public function testIncludesConstants(): void
    {
        $this->artisan('unperm:generate-ide-helper', [
            '--output' => $this->outputFile,
        ]);

        $content = File::get($this->outputFile);
        
        // Проверяем константы
        $this->assertStringContainsString('class UnPermActions', $content);
        $this->assertStringContainsString('USERS_VIEW', $content);
        $this->assertStringContainsString('POSTS_EDIT', $content);
        
        $this->assertStringContainsString('class UnPermRoles', $content);
        $this->assertStringContainsString('ADMIN', $content);
        
        $this->assertStringContainsString('class UnPermGroups', $content);
        $this->assertStringContainsString('CONTENT_TEAM', $content);
    }

    public function testIncludesDescriptions(): void
    {
        $this->artisan('unperm:generate-ide-helper', [
            '--output' => $this->outputFile,
        ]);

        $content = File::get($this->outputFile);
        
        // Проверяем что описания есть в PHPDoc
        $this->assertStringContainsString('View users', $content);
        $this->assertStringContainsString('Administrator', $content);
        $this->assertStringContainsString('Content Team', $content);
    }

    public function testFailsWhenNoPermissions(): void
    {
        // Очищаем базу
        Action::query()->delete();
        Role::query()->delete();
        Group::query()->delete();

        $this->artisan('unperm:generate-ide-helper', [
            '--output' => $this->outputFile,
        ])->assertFailed();
    }

    public function testGeneratesPhpStormMetaFile(): void
    {
        $metaFile = base_path('.phpstorm.meta.php');
        
        $this->artisan('unperm:generate-ide-helper', [
            '--output' => $this->outputFile,
            '--meta' => true,
        ])->assertSuccessful();

        $this->assertFileExists($metaFile);
        
        $content = File::get($metaFile);
        
        // Проверяем что есть override для методов
        $this->assertStringContainsString('override(\\DFiks\\UnPerm\\Traits\\HasPermissions::hasAction(0)', $content);
        $this->assertStringContainsString('override(\\DFiks\\UnPerm\\Traits\\HasPermissions::assignAction(0)', $content);
        $this->assertStringContainsString('override(\\DFiks\\UnPerm\\Traits\\HasPermissions::hasRole(0)', $content);
        
        // Проверяем что есть наши actions
        $this->assertStringContainsString("'users.view'", $content);
        $this->assertStringContainsString("'posts.edit'", $content);
        
        File::delete($metaFile);
    }
}

