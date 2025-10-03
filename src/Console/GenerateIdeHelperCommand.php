<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Console;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Group;
use DFiks\UnPerm\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateIdeHelperCommand extends Command
{
    protected $signature = 'unperm:generate-ide-helper 
                            {--output=_ide_helper_permissions.php : Output file path}';

    protected $description = 'Generate IDE helper file for permissions autocomplete';

    public function handle(): int
    {
        $this->info('Generating IDE helper for UnPerm...');

        $actions = Action::orderBy('slug')->get();
        $roles = Role::orderBy('slug')->get();
        $groups = Group::orderBy('slug')->get();

        if ($actions->isEmpty() && $roles->isEmpty() && $groups->isEmpty()) {
            $this->warn('No permissions found in database. Run unperm:sync first.');
            return self::FAILURE;
        }

        $content = $this->generateContent($actions, $roles, $groups);
        
        $outputPath = $this->option('output');
        
        // Если путь относительный, используем корень проекта
        if (!$this->isAbsolutePath($outputPath)) {
            $outputPath = base_path($outputPath);
        }

        File::put($outputPath, $content);

        $this->newLine();
        $this->info("✓ IDE helper generated successfully!");
        $this->line("  File: {$outputPath}");
        $this->newLine();
        
        $this->table(
            ['Type', 'Count'],
            [
                ['Actions', $actions->count()],
                ['Roles', $roles->count()],
                ['Groups', $groups->count()],
            ]
        );

        $this->newLine();
        $this->comment('Add this file to .gitignore if needed.');

        return self::SUCCESS;
    }

    protected function generateContent($actions, $roles, $groups): string
    {
        $timestamp = now()->toDateTimeString();
        
        $content = <<<PHP
<?php

/**
 * UnPerm IDE Helper
 * 
 * This file provides autocomplete support for IDE.
 * Generated: {$timestamp}
 * 
 * @see https://github.com/yourusername/unperm
 */

namespace DFiks\UnPerm\IdeHelper {

    /**
     * IDE Helper for HasPermissions trait
     * 
     * This class provides autocomplete for permission methods.
     * All methods work through bitmask operations for maximum performance.
     */
    class HasPermissionsIdeHelper
    {

PHP;

        // Генерируем методы для Actions
        if ($actions->isNotEmpty()) {
            $content .= "        // =============== ACTIONS ===============\n\n";
            
            foreach ($actions as $action) {
                $slug = $action->slug;
                $name = $action->name;
                $description = $action->description ? " - {$action->description}" : '';
                
                $content .= <<<PHP
        /**
         * {$name}{$description}
         * 
         * @return \$this
         */
        public function assignAction_{$this->slugToMethod($slug)}() {}

        /**
         * {$name}{$description}
         * 
         * @return \$this
         */
        public function removeAction_{$this->slugToMethod($slug)}() {}

        /**
         * Check: {$name}{$description}
         * 
         * @return bool
         */
        public function hasAction_{$this->slugToMethod($slug)}() {}


PHP;
            }
        }

        // Генерируем методы для Roles
        if ($roles->isNotEmpty()) {
            $content .= "        // =============== ROLES ===============\n\n";
            
            foreach ($roles as $role) {
                $slug = $role->slug;
                $name = $role->name;
                $description = $role->description ? " - {$role->description}" : '';
                
                $content .= <<<PHP
        /**
         * {$name}{$description}
         * 
         * @return \$this
         */
        public function assignRole_{$this->slugToMethod($slug)}() {}

        /**
         * {$name}{$description}
         * 
         * @return \$this
         */
        public function removeRole_{$this->slugToMethod($slug)}() {}

        /**
         * Check: {$name}{$description}
         * 
         * @return bool
         */
        public function hasRole_{$this->slugToMethod($slug)}() {}


PHP;
            }
        }

        // Генерируем методы для Groups
        if ($groups->isNotEmpty()) {
            $content .= "        // =============== GROUPS ===============\n\n";
            
            foreach ($groups as $group) {
                $slug = $group->slug;
                $name = $group->name;
                $description = $group->description ? " - {$group->description}" : '';
                
                $content .= <<<PHP
        /**
         * {$name}{$description}
         * 
         * @return \$this
         */
        public function assignGroup_{$this->slugToMethod($slug)}() {}

        /**
         * {$name}{$description}
         * 
         * @return \$this
         */
        public function removeGroup_{$this->slugToMethod($slug)}() {}

        /**
         * Check: {$name}{$description}
         * 
         * @return bool
         */
        public function hasGroup_{$this->slugToMethod($slug)}() {}


PHP;
            }
        }

        $content .= "    }\n";
        $content .= "}\n\n";

        // Добавляем константы для использования
        $content .= "namespace {\n\n";
        $content .= "    /**\n";
        $content .= "     * Permission Constants\n";
        $content .= "     * Use these constants instead of strings for better IDE support\n";
        $content .= "     */\n";
        $content .= "    class UnPermActions\n";
        $content .= "    {\n";
        
        foreach ($actions as $action) {
            $const = strtoupper(str_replace(['.', '-'], '_', $action->slug));
            $content .= "        public const {$const} = '{$action->slug}';\n";
        }
        
        $content .= "    }\n\n";
        
        $content .= "    class UnPermRoles\n";
        $content .= "    {\n";
        
        foreach ($roles as $role) {
            $const = strtoupper(str_replace(['.', '-'], '_', $role->slug));
            $content .= "        public const {$const} = '{$role->slug}';\n";
        }
        
        $content .= "    }\n\n";
        
        $content .= "    class UnPermGroups\n";
        $content .= "    {\n";
        
        foreach ($groups as $group) {
            $const = strtoupper(str_replace(['.', '-'], '_', $group->slug));
            $content .= "        public const {$const} = '{$group->slug}';\n";
        }
        
        $content .= "    }\n";
        $content .= "}\n";

        return $content;
    }

    protected function slugToMethod(string $slug): string
    {
        return str_replace(['.', '-', ' '], '_', $slug);
    }

    protected function isAbsolutePath(string $path): bool
    {
        // Unix-подобные системы: начинается с /
        if (str_starts_with($path, '/')) {
            return true;
        }

        // Windows: начинается с буквы диска (C:\, D:\ и т.д.)
        if (strlen($path) > 1 && $path[1] === ':') {
            return true;
        }

        return false;
    }
}

