<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Console;

use DFiks\UnPerm\Models\ResourceAction;
use DFiks\UnPerm\Support\PermBit;
use Illuminate\Console\Command;
use Throwable;

class MigrateResourceActionSlugsCommand extends Command
{
    protected $signature = 'unperm:migrate-resource-slugs {--dry-run : Show what would be changed without making changes}';

    protected $description = 'Migrate old ResourceAction slugs to new format (Folder:uuid:view -> folders.view.uuid)';

    public function handle(): int
    {
        $this->info('🔄 Migrating ResourceAction slugs to new format...');
        $this->newLine();

        $dryRun = $this->option('dry-run');

        // Получаем все ResourceActions со старым форматом
        $oldFormatActions = ResourceAction::where('slug', 'like', '%:%:%')
            ->orWhere('slug', 'not like', '%.%.%')
            ->get();

        if ($oldFormatActions->isEmpty()) {
            $this->info('✅ No ResourceActions with old slug format found!');

            return self::SUCCESS;
        }

        $this->warn("Found {$oldFormatActions->count()} ResourceActions with old format");
        $this->newLine();

        $updated = 0;
        $errors = 0;

        foreach ($oldFormatActions as $resourceAction) {
            try {
                $oldSlug = $resourceAction->slug;

                // Получаем resource
                $resourceClass = $resourceAction->resource_type;

                if (!class_exists($resourceClass)) {
                    $this->error("  ❌ Class not found: {$resourceClass}");
                    $errors++;
                    continue;
                }

                $resource = $resourceClass::find($resourceAction->resource_id);

                if (!$resource) {
                    $this->warn("  ⚠️  Resource not found: {$resourceClass}::{$resourceAction->resource_id}");
                    $this->line('     Will use fallback slug generation');

                    // Fallback: используем базовое имя класса и table name
                    $model = new $resourceClass();
                    $resourceKey = method_exists($model, 'getResourcePermissionKey')
                        ? $model->getResourcePermissionKey()
                        : (method_exists($model, 'getTable') ? $model->getTable() : strtolower(class_basename($resourceClass)));

                    $newSlug = sprintf(
                        '%s.%s.%s',
                        $resourceKey,
                        $resourceAction->action_type,
                        $resourceAction->resource_id
                    );
                } else {
                    // Используем метод модели для генерации правильного slug
                    if (method_exists($resource, 'getResourcePermissionSlug')) {
                        $newSlug = $resource->getResourcePermissionSlug($resourceAction->action_type);
                    } else {
                        // Fallback
                        $resourceKey = method_exists($resource, 'getResourcePermissionKey')
                            ? $resource->getResourcePermissionKey()
                            : $resource->getTable();

                        $newSlug = sprintf(
                            '%s.%s.%s',
                            $resourceKey,
                            $resourceAction->action_type,
                            $resource->getKey()
                        );
                    }
                }

                if ($oldSlug === $newSlug) {
                    continue; // Уже в правильном формате
                }

                $this->line("  📝 {$oldSlug}");
                $this->line("     → {$newSlug}");

                if (!$dryRun) {
                    $resourceAction->slug = $newSlug;
                    $resourceAction->save();
                    $updated++;
                } else {
                    $this->line('     [DRY RUN - not saved]');
                    $updated++;
                }

            } catch (Throwable $e) {
                $this->error("  ❌ Error processing {$resourceAction->slug}: " . $e->getMessage());
                $errors++;
            }
        }

        $this->newLine();

        if ($dryRun) {
            $this->warn("DRY RUN: Would update {$updated} ResourceActions");
            $this->line('Run without --dry-run to apply changes');
        } else {
            $this->info("✅ Updated {$updated} ResourceActions");

            if ($updated > 0) {
                $this->line('Rebuilding bitmask...');
                PermBit::rebuild();
                $this->info('✅ Bitmask rebuilt');
            }
        }

        if ($errors > 0) {
            $this->warn("⚠️  {$errors} errors occurred");
        }

        return self::SUCCESS;
    }
}
