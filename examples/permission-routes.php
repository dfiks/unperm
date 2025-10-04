<?php

/**
 * Примеры маршрутов для управления разрешениями.
 *
 * Скопируйте в ваш routes/web.php или routes/api.php и адаптируйте под свои нужды.
 */

use App\Http\Controllers\Admin\Permission\ActionManagementController;
use App\Http\Controllers\Admin\Permission\GroupManagementController;
use App\Http\Controllers\Admin\Permission\RoleManagementController;
use App\Http\Controllers\Admin\Permission\UserPermissionManagementController;
use Illuminate\Support\Facades\Route;

// ============================================================================
// WEB ROUTES - для веб-интерфейса
// ============================================================================

Route::prefix('admin/permissions')->middleware(['auth'])->group(function () {

    // ========== ACTIONS ==========
    Route::prefix('actions')->name('admin.permissions.actions.')->group(function () {
        // Список
        Route::get('/', [ActionManagementController::class, 'index'])
            ->name('index');

        // Просмотр
        Route::get('/{id}', [ActionManagementController::class, 'show'])
            ->name('show');

        // Создание
        Route::get('/create/form', [ActionManagementController::class, 'create'])
            ->name('create');
        Route::post('/', [ActionManagementController::class, 'store'])
            ->name('store');

        // Редактирование
        Route::get('/{id}/edit', [ActionManagementController::class, 'edit'])
            ->name('edit');
        Route::put('/{id}', [ActionManagementController::class, 'update'])
            ->name('update');

        // Удаление
        Route::delete('/{id}', [ActionManagementController::class, 'destroy'])
            ->name('destroy');

        // Синхронизация из конфига
        Route::post('/sync', [ActionManagementController::class, 'sync'])
            ->name('sync');
    });

    // ========== ROLES ==========
    Route::prefix('roles')->name('admin.permissions.roles.')->group(function () {
        Route::get('/', [RoleManagementController::class, 'index'])
            ->name('index');
        Route::get('/create/form', [RoleManagementController::class, 'create'])
            ->name('create');
        Route::post('/', [RoleManagementController::class, 'store'])
            ->name('store');
        Route::get('/{id}', [RoleManagementController::class, 'show'])
            ->name('show');
        Route::get('/{id}/edit', [RoleManagementController::class, 'edit'])
            ->name('edit');
        Route::put('/{id}', [RoleManagementController::class, 'update'])
            ->name('update');
        Route::delete('/{id}', [RoleManagementController::class, 'destroy'])
            ->name('destroy');

        // Управление actions
        Route::post('/{id}/actions', [RoleManagementController::class, 'attachAction'])
            ->name('attach-action');
        Route::delete('/{id}/actions/{actionId}', [RoleManagementController::class, 'detachAction'])
            ->name('detach-action');
        Route::put('/{id}/actions', [RoleManagementController::class, 'syncActions'])
            ->name('sync-actions');

        // Синхронизация
        Route::post('/sync', [RoleManagementController::class, 'sync'])
            ->name('sync');
    });

    // ========== GROUPS ==========
    Route::prefix('groups')->name('admin.permissions.groups.')->group(function () {
        Route::get('/', [GroupManagementController::class, 'index'])
            ->name('index');
        Route::get('/create/form', [GroupManagementController::class, 'create'])
            ->name('create');
        Route::post('/', [GroupManagementController::class, 'store'])
            ->name('store');
        Route::get('/{id}', [GroupManagementController::class, 'show'])
            ->name('show');
        Route::get('/{id}/edit', [GroupManagementController::class, 'edit'])
            ->name('edit');
        Route::put('/{id}', [GroupManagementController::class, 'update'])
            ->name('update');
        Route::delete('/{id}', [GroupManagementController::class, 'destroy'])
            ->name('destroy');

        // Управление actions и roles
        Route::post('/{id}/actions', [GroupManagementController::class, 'attachAction'])
            ->name('attach-action');
        Route::post('/{id}/roles', [GroupManagementController::class, 'attachRole'])
            ->name('attach-role');

        // Синхронизация
        Route::post('/sync', [GroupManagementController::class, 'sync'])
            ->name('sync');
    });

    // ========== USERS ==========
    Route::prefix('users')->name('admin.permissions.users.')->group(function () {
        // Список пользователей
        Route::get('/', [UserPermissionManagementController::class, 'index'])
            ->name('index');

        // Просмотр разрешений пользователя
        Route::get('/{id}', [UserPermissionManagementController::class, 'show'])
            ->name('show');

        // Редактирование разрешений
        Route::get('/{id}/edit', [UserPermissionManagementController::class, 'edit'])
            ->name('edit');

        // Назначение прав
        Route::post('/{id}/actions', [UserPermissionManagementController::class, 'assignAction'])
            ->name('assign-action');
        Route::delete('/{id}/actions/{actionId}', [UserPermissionManagementController::class, 'removeAction'])
            ->name('remove-action');

        Route::post('/{id}/roles', [UserPermissionManagementController::class, 'assignRole'])
            ->name('assign-role');
        Route::delete('/{id}/roles/{roleId}', [UserPermissionManagementController::class, 'removeRole'])
            ->name('remove-role');

        Route::post('/{id}/groups', [UserPermissionManagementController::class, 'assignGroup'])
            ->name('assign-group');
        Route::delete('/{id}/groups/{groupId}', [UserPermissionManagementController::class, 'removeGroup'])
            ->name('remove-group');

        // Синхронизация всех разрешений
        Route::put('/{id}/permissions', [UserPermissionManagementController::class, 'syncPermissions'])
            ->name('sync-permissions');

        // Массовые операции
        Route::post('/bulk/assign-role', [UserPermissionManagementController::class, 'bulkAssignRole'])
            ->name('bulk-assign-role');
        Route::post('/bulk/assign-group', [UserPermissionManagementController::class, 'bulkAssignGroup'])
            ->name('bulk-assign-group');
    });
});

// ============================================================================
// API ROUTES - для API интерфейса
// ============================================================================

Route::prefix('api/admin/permissions')->middleware(['auth:sanctum'])->group(function () {

    // Actions API
    Route::apiResource('actions', ActionManagementController::class);
    Route::post('actions/sync', [ActionManagementController::class, 'sync']);

    // Roles API
    Route::apiResource('roles', RoleManagementController::class);
    Route::post('roles/{id}/actions', [RoleManagementController::class, 'attachAction']);
    Route::delete('roles/{id}/actions/{actionId}', [RoleManagementController::class, 'detachAction']);
    Route::put('roles/{id}/actions', [RoleManagementController::class, 'syncActions']);
    Route::post('roles/sync', [RoleManagementController::class, 'sync']);

    // Groups API
    Route::apiResource('groups', GroupManagementController::class);
    Route::post('groups/{id}/actions', [GroupManagementController::class, 'attachAction']);
    Route::post('groups/{id}/roles', [GroupManagementController::class, 'attachRole']);
    Route::post('groups/sync', [GroupManagementController::class, 'sync']);

    // Users API
    Route::get('users', [UserPermissionManagementController::class, 'index']);
    Route::get('users/{id}', [UserPermissionManagementController::class, 'show']);
    Route::post('users/{id}/actions', [UserPermissionManagementController::class, 'assignAction']);
    Route::delete('users/{id}/actions/{actionId}', [UserPermissionManagementController::class, 'removeAction']);
    Route::post('users/{id}/roles', [UserPermissionManagementController::class, 'assignRole']);
    Route::delete('users/{id}/roles/{roleId}', [UserPermissionManagementController::class, 'removeRole']);
    Route::post('users/{id}/groups', [UserPermissionManagementController::class, 'assignGroup']);
    Route::delete('users/{id}/groups/{groupId}', [UserPermissionManagementController::class, 'removeGroup']);
    Route::put('users/{id}/permissions', [UserPermissionManagementController::class, 'syncPermissions']);
    Route::post('users/bulk/assign-role', [UserPermissionManagementController::class, 'bulkAssignRole']);
    Route::post('users/bulk/assign-group', [UserPermissionManagementController::class, 'bulkAssignGroup']);
});

// ============================================================================
// УПРОЩЕННЫЕ МАРШРУТЫ - для быстрого старта
// ============================================================================

// Если вам нужны только базовые операции, используйте apiResource:

Route::middleware(['auth'])->prefix('admin/permissions')->group(function () {
    Route::apiResource('actions', ActionManagementController::class);
    Route::apiResource('roles', RoleManagementController::class);
    Route::apiResource('groups', GroupManagementController::class);
});

// ============================================================================
// ЗАЩИТА МАРШРУТОВ РАЗРЕШЕНИЯМИ
// ============================================================================

// С проверкой конкретного разрешения:
Route::middleware(['auth', 'unperm:admin.permissions.view'])->group(function () {
    // Все маршруты здесь требуют admin.permissions.view
});

// Разные уровни доступа:
Route::prefix('admin/permissions')->middleware(['auth'])->group(function () {

    // Просмотр - требует admin.permissions.view
    Route::get('actions', [ActionManagementController::class, 'index'])
        ->middleware('unperm:admin.permissions.view');

    // Создание/редактирование - требует admin.permissions.manage
    Route::post('actions', [ActionManagementController::class, 'store'])
        ->middleware('unperm:admin.permissions.manage');

    Route::put('actions/{id}', [ActionManagementController::class, 'update'])
        ->middleware('unperm:admin.permissions.manage');

    Route::delete('actions/{id}', [ActionManagementController::class, 'destroy'])
        ->middleware('unperm:admin.permissions.manage');
});

// ============================================================================
// КАСТОМНАЯ ЛОГИКА АВТОРИЗАЦИИ
// ============================================================================

Route::get('admin/permissions/dashboard', function () {
    // Проверка через helper
    if (!currentUserHasRole('admin')) {
        abort(403, 'Только для администраторов');
    }

    return view('admin.permissions.dashboard');
})->middleware('auth');

// Или через middleware:
Route::get('admin/permissions/settings', function () {
    return view('admin.permissions.settings');
})->middleware(['auth', function ($request, $next) {
    if (!isSuperadmin()) {
        abort(403, 'Только для супер-админов');
    }

    return $next($request);
}]);
