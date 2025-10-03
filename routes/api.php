<?php

use DFiks\UnPerm\Http\Controllers\Api\ActionController;
use DFiks\UnPerm\Http\Controllers\Api\RoleController;
use DFiks\UnPerm\Http\Controllers\Api\GroupController;
use DFiks\UnPerm\Http\Controllers\Api\UserPermissionController;
use DFiks\UnPerm\Http\Controllers\Api\ResourcePermissionController;
use DFiks\UnPerm\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])
    ->prefix('unperm/api')
    ->name('unperm.api.')
    ->group(function () {
        
        // Dashboard
        Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
        
        // Actions
        Route::apiResource('actions', ActionController::class);
        Route::post('actions/sync', [ActionController::class, 'sync'])->name('actions.sync');
        
        // Roles
        Route::apiResource('roles', RoleController::class);
        Route::post('roles/sync', [RoleController::class, 'sync'])->name('roles.sync');
        Route::post('roles/{role}/actions', [RoleController::class, 'assignActions'])->name('roles.assign-actions');
        
        // Groups
        Route::apiResource('groups', GroupController::class);
        Route::post('groups/sync', [GroupController::class, 'sync'])->name('groups.sync');
        Route::post('groups/{group}/actions', [GroupController::class, 'assignActions'])->name('groups.assign-actions');
        Route::post('groups/{group}/roles', [GroupController::class, 'assignRoles'])->name('groups.assign-roles');
        
        // User Permissions
        Route::get('/user-models', [UserPermissionController::class, 'models'])->name('user-models');
        Route::get('/users', [UserPermissionController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [UserPermissionController::class, 'show'])->name('users.show');
        Route::post('/users/{user}/permissions', [UserPermissionController::class, 'update'])->name('users.permissions.update');
        
        // Resource Permissions
        Route::get('/resource-models', [ResourcePermissionController::class, 'models'])->name('resource-models');
        Route::get('/resources', [ResourcePermissionController::class, 'index'])->name('resources.index');
        Route::get('/resources/{resource}', [ResourcePermissionController::class, 'show'])->name('resources.show');
        Route::post('/resources/{resource}/grant', [ResourcePermissionController::class, 'grant'])->name('resources.grant');
        Route::post('/resources/{resource}/revoke', [ResourcePermissionController::class, 'revoke'])->name('resources.revoke');
    });

