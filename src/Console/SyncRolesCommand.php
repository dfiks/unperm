<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Console;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncRolesCommand extends Command
{
    protected $signature = 'unperm:sync-roles {--fresh : Delete all existing roles before sync}';

    protected $description = 'Sync roles from config to database with actions';

    public function handle(): int
    {
        $this->info('Syncing roles from config...');

        if ($this->option('fresh')) {
            $this->warn('Deleting all existing roles...');
            Role::query()->delete();
        }

        $rolesConfig = config('unperm.roles', []);

        if (empty($rolesConfig)) {
            $this->warn('No roles found in config.');

            return self::FAILURE;
        }

        $created = 0;
        $updated = 0;

        foreach ($rolesConfig as $slug => $data) {
            $role = Role::firstOrNew(['slug' => $slug]);

            $isNew = !$role->exists;

            $role->name = $data['name'] ?? Str::title(str_replace('-', ' ', $slug));
            $role->description = $data['description'] ?? null;
            $role->bitmask = '0';

            $role->save();

            // Синхронизируем actions
            $actionIds = $this->resolveActions($data['actions'] ?? []);
            $role->actions()->sync($actionIds);

            // Пересчитываем bitmask
            $role->load('actions')->syncBitmaskFromActions()->save();

            if ($isNew) {
                $created++;
                $this->line("  <fg=green>✓</> Created: {$slug} with " . count($actionIds) . ' actions');
            } else {
                $updated++;
                $this->line("  <fg=yellow>↻</> Updated: {$slug} with " . count($actionIds) . ' actions');
            }
        }

        $this->newLine();
        $this->info('✓ Sync completed!');
        $this->table(
            ['Status', 'Count'],
            [
                ['Created', $created],
                ['Updated', $updated],
                ['Total', $created + $updated],
            ]
        );

        $this->newLine();
        $this->comment('Cleaning up old roles...');

        $configSlugs = array_keys($rolesConfig);
        $deleted = Role::whereNotIn('slug', $configSlugs)->delete();

        if ($deleted > 0) {
            $this->warn("Deleted {$deleted} roles that are no longer in config.");
        } else {
            $this->info('No orphaned roles found.');
        }

        return self::SUCCESS;
    }

    protected function resolveActions(array $patterns): array
    {
        if (empty($patterns)) {
            return [];
        }

        $actionIds = [];
        $allActions = Action::all();

        if ($allActions->isEmpty()) {
            $this->warn('No actions found in database. Run unperm:sync-actions first.');

            return [];
        }

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                // Конвертируем wildcard в regex: экранируем спецсимволы кроме *, потом заменяем * на .*
                $escapedPattern = str_replace('*', '__WILDCARD__', $pattern);
                $escapedPattern = preg_quote($escapedPattern, '/');
                $regex = '/^' . str_replace('__WILDCARD__', '.*', $escapedPattern) . '$/';

                foreach ($allActions as $action) {
                    if (preg_match($regex, $action->slug)) {
                        $actionIds[] = $action->id;
                    }
                }
            } else {
                $action = $allActions->firstWhere('slug', $pattern);
                if ($action) {
                    $actionIds[] = $action->id;
                } else {
                    $this->warn("Action '{$pattern}' not found.");
                }
            }
        }

        return array_unique($actionIds);
    }
}
