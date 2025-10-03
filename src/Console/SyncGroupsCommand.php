<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Console;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Group;
use DFiks\UnPerm\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncGroupsCommand extends Command
{
    protected $signature = 'unperm:sync-groups {--fresh : Delete all existing groups before sync}';

    protected $description = 'Sync groups from config to database with roles and actions';

    public function handle(): int
    {
        $this->info('Syncing groups from config...');

        if ($this->option('fresh')) {
            $this->warn('Deleting all existing groups...');
            Group::query()->delete();
        }

        $groupsConfig = config('unperm.groups', []);

        if (empty($groupsConfig)) {
            $this->warn('No groups found in config.');

            return self::FAILURE;
        }

        $created = 0;
        $updated = 0;

        foreach ($groupsConfig as $slug => $data) {
            $group = Group::firstOrNew(['slug' => $slug]);

            $isNew = !$group->exists;

            $group->name = $data['name'] ?? Str::title(str_replace('-', ' ', $slug));
            $group->description = $data['description'] ?? null;
            $group->bitmask = '0';

            $group->save();

            // Синхронизируем roles
            $roleIds = $this->resolveRoles($data['roles'] ?? []);
            $group->roles()->sync($roleIds);

            // Синхронизируем actions
            $actionIds = $this->resolveActions($data['actions'] ?? []);
            $group->actions()->sync($actionIds);

            // Пересчитываем bitmask
            $group->load(['roles', 'actions'])->syncBitmaskFromRolesAndActions()->save();

            if ($isNew) {
                $created++;
                $this->line("  <fg=green>✓</> Created: {$slug} with " . count($roleIds) . ' roles and ' . count($actionIds) . ' actions');
            } else {
                $updated++;
                $this->line("  <fg=yellow>↻</> Updated: {$slug} with " . count($roleIds) . ' roles and ' . count($actionIds) . ' actions');
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
        $this->comment('Cleaning up old groups...');

        $configSlugs = array_keys($groupsConfig);
        $deleted = Group::whereNotIn('slug', $configSlugs)->delete();

        if ($deleted > 0) {
            $this->warn("Deleted {$deleted} groups that are no longer in config.");
        } else {
            $this->info('No orphaned groups found.');
        }

        return self::SUCCESS;
    }

    protected function resolveRoles(array $slugs): array
    {
        if (empty($slugs)) {
            return [];
        }

        return Role::whereIn('slug', $slugs)->pluck('id')->toArray();
    }

    protected function resolveActions(array $patterns): array
    {
        if (empty($patterns)) {
            return [];
        }

        $actionIds = [];
        $allActions = Action::all();

        if ($allActions->isEmpty()) {
            $this->warn('No actions found in database.');

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
                }
            }
        }

        return array_unique($actionIds);
    }
}
