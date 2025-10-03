<?php

declare(strict_types=1);

namespace DFiks\UnPerm;

use DFiks\UnPerm\Console\RebuildBitmaskCommand;
use DFiks\UnPerm\Console\SyncActionsCommand;
use DFiks\UnPerm\Services\PermissionChecker;
use Illuminate\Support\ServiceProvider;

class UnPermServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/unperm.php',
            'unperm'
        );

        $this->app->singleton('unperm', function ($app) {
            return new PermissionChecker();
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/unperm.php' => config_path('unperm.php'),
            ], 'unperm-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'unperm-migrations');

            $this->commands([
                SyncActionsCommand::class,
                \DFiks\UnPerm\Console\SyncRolesCommand::class,
                \DFiks\UnPerm\Console\SyncGroupsCommand::class,
                \DFiks\UnPerm\Console\SyncPermissionsCommand::class,
                RebuildBitmaskCommand::class,
                \DFiks\UnPerm\Console\GenerateIdeHelperCommand::class,
            ]);
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    public function provides(): array
    {
        return [
            'unperm',
        ];
    }
}
