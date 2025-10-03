<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Console;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Support\PermBit;
use Illuminate\Console\Command;

class SyncActionsCommand extends Command
{
    protected $signature = 'unperm:sync-actions {--fresh : Delete all existing actions before sync}';

    protected $description = 'Sync actions from config to database with auto-generated bitmasks';

    public function handle(): int
    {
        $this->info('Syncing actions from config...');

        if ($this->option('fresh')) {
            $this->warn('Deleting all existing actions...');
            Action::query()->delete();
        }

        $actions = PermBit::rebuild();

        if (empty($actions)) {
            $this->warn('No actions found in config.');

            return self::FAILURE;
        }

        $created = 0;
        $updated = 0;

        foreach ($actions as $slug => $data) {
            $action = Action::firstOrNew(['slug' => $slug]);

            $isNew = !$action->exists;

            $action->name = $data['name'];
            $action->description = "Category: {$data['category']} | Bit: {$data['bit_position']}";
            $action->bitmask = $data['bitmask'];

            $action->save();

            if ($isNew) {
                $created++;
                $this->line("  <fg=green>✓</> Created: {$slug} (bit {$data['bit_position']}, mask: {$data['bitmask_hex']})");
            } else {
                $updated++;
                $this->line("  <fg=yellow>↻</> Updated: {$slug} (bit {$data['bit_position']}, mask: {$data['bitmask_hex']})");
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
        $this->comment('Cleaning up old actions...');

        $configSlugs = array_keys($actions);
        $deleted = Action::whereNotIn('slug', $configSlugs)->delete();

        if ($deleted > 0) {
            $this->warn("Deleted {$deleted} actions that are no longer in config.");
        } else {
            $this->info('No orphaned actions found.');
        }

        return self::SUCCESS;
    }
}
