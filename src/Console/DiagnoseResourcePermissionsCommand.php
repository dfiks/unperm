<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Console;

use DFiks\UnPerm\Models\ResourceAction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnoseResourcePermissionsCommand extends Command
{
    protected $signature = 'unperm:diagnose-resources
                            {user-model? : User model class (e.g., App\\Models\\User)}
                            {user-id? : User ID to check}
                            {resource-model? : Resource model class (e.g., App\\Models\\Folder)}';

    protected $description = 'Diagnose resource permissions setup and issues';

    public function handle(): int
    {
        $this->info('🔍 UnPerm Resource Permissions Diagnostics');
        $this->newLine();

        // 1. Проверяем ResourceActions
        $this->checkResourceActions();

        // 2. Проверяем pivot таблицу
        $this->checkPivotTable();

        // 3. Если указан пользователь, проверяем его права
        if ($this->argument('user-model') && $this->argument('user-id')) {
            $this->checkUserPermissions();
        }

        // 4. Проверяем связь с глобальными actions
        $this->checkGlobalActions();

        return self::SUCCESS;
    }

    protected function checkResourceActions(): void
    {
        $this->line('📦 <fg=cyan>Checking ResourceActions table...</>');

        $count = ResourceAction::count();
        $this->info("   Total ResourceActions: {$count}");

        if ($count === 0) {
            $this->warn('   ⚠️  No ResourceActions found! Create some by granting permissions on specific resources.');
            return;
        }

        // Группируем по типам
        $byType = ResourceAction::selectRaw('resource_type, action_type, COUNT(*) as count')
            ->groupBy('resource_type', 'action_type')
            ->get();

        $this->table(
            ['Resource Type', 'Action Type', 'Count'],
            $byType->map(fn($item) => [
                class_basename($item->resource_type),
                $item->action_type,
                $item->count,
            ])->toArray()
        );

        // Показываем примеры slug
        $examples = ResourceAction::select('slug', 'action_type', 'resource_type')
            ->limit(5)
            ->get();

        $this->line('   <fg=yellow>Sample slugs:</>');
        foreach ($examples as $example) {
            $this->line("   • {$example->slug}");
        }

        $this->newLine();
    }

    protected function checkPivotTable(): void
    {
        $this->line('🔗 <fg=cyan>Checking model_resource_actions pivot table...</>');

        $count = DB::table('model_resource_actions')->count();
        $this->info("   Total pivot records: {$count}");

        if ($count === 0) {
            $this->warn('   ⚠️  No user-resource links found!');
            return;
        }

        // Группируем по типам моделей
        $byModelType = DB::table('model_resource_actions')
            ->select('model_type', DB::raw('COUNT(*) as count'))
            ->groupBy('model_type')
            ->get();

        $this->table(
            ['User Model Type', 'Links Count'],
            $byModelType->map(fn($item) => [
                $item->model_type,
                $item->count,
            ])->toArray()
        );

        $this->newLine();
    }

    protected function checkUserPermissions(): void
    {
        $userClass = $this->argument('user-model');
        $userId = $this->argument('user-id');

        $this->line("👤 <fg=cyan>Checking permissions for {$userClass}::{$userId}...</>");

        if (!class_exists($userClass)) {
            $this->error("   ❌ Class {$userClass} not found!");
            return;
        }

        $user = $userClass::find($userId);
        if (!$user) {
            $this->error("   ❌ User not found!");
            return;
        }

        $this->info("   User: " . ($user->name ?? $user->email ?? $userId));

        // Проверяем трейты
        $traits = class_uses_recursive($user);
        $hasPermissionsTrait = in_array('DFiks\\UnPerm\\Traits\\HasPermissions', $traits);
        
        if ($hasPermissionsTrait) {
            $this->info('   ✅ Uses HasPermissions trait');
        } else {
            $this->error('   ❌ Does NOT use HasPermissions trait!');
            $this->warn('   Add: use \\DFiks\\UnPerm\\Traits\\HasPermissions;');
            return;
        }

        // Загружаем связи
        $user->load(['actions', 'resourceActions']);

        $this->info("   Global actions: " . $user->actions->count());
        $this->info("   Resource actions: " . $user->resourceActions->count());

        if ($user->resourceActions->count() > 0) {
            $this->line('   <fg=yellow>User ResourceActions:</>');
            foreach ($user->resourceActions->take(10) as $ra) {
                $this->line("   • {$ra->slug}");
            }
        }

        // Если указана модель ресурса, проверяем доступ
        if ($resourceClass = $this->argument('resource-model')) {
            $this->checkResourceAccess($user, $resourceClass);
        }

        $this->newLine();
    }

    protected function checkResourceAccess($user, string $resourceClass): void
    {
        if (!class_exists($resourceClass)) {
            $this->error("   ❌ Resource class {$resourceClass} not found!");
            return;
        }

        $this->line("   <fg=yellow>Checking access to {$resourceClass}:</>");

        // Проверяем трейт
        $traits = class_uses_recursive(new $resourceClass());
        $hasResourcePermissions = in_array('DFiks\\UnPerm\\Traits\\HasResourcePermissions', $traits);

        if (!$hasResourcePermissions) {
            $this->error("   ❌ {$resourceClass} does NOT use HasResourcePermissions trait!");
            return;
        }

        $this->info('   ✅ Uses HasResourcePermissions trait');

        // Пробуем получить доступные ресурсы
        $viewable = $resourceClass::viewableBy($user)->limit(10)->get();
        $this->info("   Viewable resources: " . $viewable->count());

        if ($viewable->count() > 0) {
            foreach ($viewable as $resource) {
                $name = $resource->name ?? $resource->title ?? $resource->id;
                $this->line("   • {$name} (ID: {$resource->id})");
            }
        }
    }

    protected function checkGlobalActions(): void
    {
        $this->line('🌐 <fg=cyan>Checking global actions vs resource actions...</>');

        // Получаем все уникальные комбинации resource_type + action_type
        $resourceGroups = ResourceAction::selectRaw('resource_type, action_type')
            ->groupBy('resource_type', 'action_type')
            ->get();

        if ($resourceGroups->isEmpty()) {
            $this->info('   No resource actions to check');
            return;
        }

        $orphaned = [];
        foreach ($resourceGroups as $group) {
            // Пытаемся получить resource key
            $resourceKey = $this->getResourceKey($group->resource_type);
            $expectedSlug = $resourceKey . '.' . $group->action_type;

            // Проверяем есть ли глобальный action
            $exists = DB::table('actions')->where('slug', $expectedSlug)->exists();

            if (!$exists) {
                $orphaned[] = [
                    'type' => class_basename($group->resource_type),
                    'action' => $group->action_type,
                    'expected_slug' => $expectedSlug,
                ];
            }
        }

        if (empty($orphaned)) {
            $this->info('   ✅ All resource actions have corresponding global actions');
        } else {
            $this->warn('   ⚠️  Found orphaned resource actions (no global action):');
            $this->table(
                ['Resource Type', 'Action', 'Expected Global Action Slug'],
                $orphaned
            );
            $this->line('   💡 Create these global actions or they won\'t show in UI');
        }

        $this->newLine();
    }

    protected function getResourceKey(string $resourceType): string
    {
        if (!class_exists($resourceType)) {
            return strtolower(class_basename($resourceType));
        }

        try {
            $model = new $resourceType();
            
            if (method_exists($model, 'getResourcePermissionKey')) {
                return $model->getResourcePermissionKey();
            }
            
            if (method_exists($model, 'getTable')) {
                return $model->getTable();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return strtolower(class_basename($resourceType));
    }
}

