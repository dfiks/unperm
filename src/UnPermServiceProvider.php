<?php

declare(strict_types=1);

namespace DFiks\UnPerm;

use DFiks\UnPerm\Console\AnalyzeBitmaskCommand;
use DFiks\UnPerm\Console\GenerateIdeHelperCommand;
use DFiks\UnPerm\Console\RebuildBitmaskCommand;
use DFiks\UnPerm\Console\SyncActionsCommand;
use DFiks\UnPerm\Console\SyncGroupsCommand;
use DFiks\UnPerm\Console\SyncPermissionsCommand;
use DFiks\UnPerm\Console\SyncRolesCommand;
use DFiks\UnPerm\Middleware\CheckResourcePermission;
use DFiks\UnPerm\Services\PermissionChecker;
use Illuminate\Support\Facades\Route;
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

        $this->app->singleton('unperm.gate', function ($app) {
            return new Support\PermissionGate();
        });

        // Загружаем helpers
        if (file_exists(__DIR__ . '/helpers.php')) {
            require_once __DIR__ . '/helpers.php';
        }
    }

    public function boot(): void
    {
        // Публикация конфигурации
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/unperm.php' => config_path('unperm.php'),
            ], 'unperm-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'unperm-migrations');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/unperm'),
            ], 'unperm-views');

            $this->commands([
                SyncActionsCommand::class,
                SyncRolesCommand::class,
                SyncGroupsCommand::class,
                SyncPermissionsCommand::class,
                RebuildBitmaskCommand::class,
                GenerateIdeHelperCommand::class,
                AnalyzeBitmaskCommand::class,
                Console\ListModelsCommand::class,
                Console\DiagnoseResourcePermissionsCommand::class,
                Console\MigrateResourceActionSlugsCommand::class,
            ]);
        }

        // Загрузка views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'unperm');

        // Загрузка routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Загрузка API routes
        if (file_exists(__DIR__ . '/../routes/api.php')) {
            Route::group([
                'prefix' => 'api',
                'middleware' => 'api',
            ], function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
            });
        }

        // Загрузка миграций
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Регистрация Livewire компонентов
        if (class_exists(\Livewire\Livewire::class)) {
            \Livewire\Livewire::component('unperm::manage-actions', Http\Livewire\ManageActions::class);
            \Livewire\Livewire::component('unperm::manage-roles', Http\Livewire\ManageRoles::class);
            \Livewire\Livewire::component('unperm::manage-groups', Http\Livewire\ManageGroups::class);
            \Livewire\Livewire::component('unperm::manage-user-permissions', Http\Livewire\ManageUserPermissions::class);
            \Livewire\Livewire::component('unperm::manage-resource-permissions', Http\Livewire\ManageResourcePermissions::class);
        }

        // Регистрация middleware
        if (method_exists($this->app, 'make')) {
            $router = $this->app->make('router');
            $router->aliasMiddleware('unperm', CheckResourcePermission::class);
        }
    }

    public function provides(): array
    {
        return ['unperm'];
    }
}
