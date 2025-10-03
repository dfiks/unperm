<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Console;

use Illuminate\Console\Command;

class SyncPermissionsCommand extends Command
{
    protected $signature = 'unperm:sync {--fresh : Delete all existing data before sync}';

    protected $description = 'Sync all permissions (actions, roles, groups) from config';

    public function handle(): int
    {
        $this->info('Starting full permissions sync...');
        $this->newLine();

        $options = $this->option('fresh') ? ['--fresh' => true] : [];

        // 1. Синхронизируем actions
        $this->call('unperm:sync-actions', $options);
        $this->newLine();

        // 2. Синхронизируем roles (зависят от actions)
        $this->call('unperm:sync-roles', $options);
        $this->newLine();

        // 3. Синхронизируем groups (зависят от roles и actions)
        $this->call('unperm:sync-groups', $options);
        $this->newLine();

        $this->info('✓ Full permissions sync completed!');

        return self::SUCCESS;
    }
}
