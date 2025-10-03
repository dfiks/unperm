<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Console;

use DFiks\UnPerm\Models\Group;
use DFiks\UnPerm\Models\Role;
use Illuminate\Console\Command;

class RebuildBitmaskCommand extends Command
{
    protected $signature = 'unperm:rebuild-bitmask {--roles : Rebuild only roles} {--groups : Rebuild only groups}';

    protected $description = 'Rebuild bitmasks for roles and groups based on their actions';

    public function handle(): int
    {
        $rebuildRoles = $this->option('roles') || (!$this->option('roles') && !$this->option('groups'));
        $rebuildGroups = $this->option('groups') || (!$this->option('roles') && !$this->option('groups'));

        if ($rebuildRoles) {
            $this->info('Rebuilding bitmasks for roles...');
            $this->rebuildRoles();
        }

        if ($rebuildGroups) {
            $this->info('Rebuilding bitmasks for groups...');
            $this->rebuildGroups();
        }

        $this->newLine();
        $this->info('✓ Bitmask rebuild completed!');

        return self::SUCCESS;
    }

    protected function rebuildRoles(): void
    {
        $roles = Role::with('actions')->get();
        $updated = 0;

        foreach ($roles as $role) {
            $oldBitmask = $role->bitmask;
            $role->syncBitmaskFromActions()->save();
            
            if ($oldBitmask !== $role->bitmask) {
                $updated++;
                $this->line("  <fg=green>✓</> {$role->name}: {$oldBitmask} → {$role->bitmask}");
            } else {
                $this->line("  <fg=gray>-</> {$role->name}: {$role->bitmask} (unchanged)");
            }
        }

        $this->newLine();
        $this->info("Updated {$updated} of {$roles->count()} roles.");
    }

    protected function rebuildGroups(): void
    {
        $groups = Group::with(['roles', 'actions'])->get();
        $updated = 0;

        foreach ($groups as $group) {
            $oldBitmask = $group->bitmask;
            $group->syncBitmaskFromRolesAndActions()->save();
            
            if ($oldBitmask !== $group->bitmask) {
                $updated++;
                $this->line("  <fg=green>✓</> {$group->name}: {$oldBitmask} → {$group->bitmask}");
            } else {
                $this->line("  <fg=gray>-</> {$group->name}: {$group->bitmask} (unchanged)");
            }
        }

        $this->newLine();
        $this->info("Updated {$updated} of {$groups->count()} groups.");
    }
}

