<?php

use DFiks\UnPerm\Http\Controllers\Api\ActionsApiController;
use DFiks\UnPerm\Http\Controllers\Api\GroupsApiController;
use DFiks\UnPerm\Http\Controllers\Api\ResourcePermissionsApiController;
use DFiks\UnPerm\Http\Controllers\Api\RolesApiController;
use DFiks\UnPerm\Http\Controllers\Api\UsersApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('unperm')->name('unperm.api.')->group(function () {

    Route::prefix('actions')->name('actions.')->group(function () {
        Route::get('/', [ActionsApiController::class, 'index'])->name('index');
        Route::get('/{id}', [ActionsApiController::class, 'show'])->name('show');
        Route::post('/', [ActionsApiController::class, 'store'])->name('store');
        Route::put('/{id}', [ActionsApiController::class, 'update'])->name('update');
        Route::delete('/{id}', [ActionsApiController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RolesApiController::class, 'index'])->name('index');
        Route::get('/{id}', [RolesApiController::class, 'show'])->name('show');
        Route::post('/', [RolesApiController::class, 'store'])->name('store');
        Route::put('/{id}', [RolesApiController::class, 'update'])->name('update');
        Route::delete('/{id}', [RolesApiController::class, 'destroy'])->name('destroy');

        Route::post('/{id}/actions', [RolesApiController::class, 'attachAction'])->name('attach-action');
        Route::delete('/{id}/actions/{actionId}', [RolesApiController::class, 'detachAction'])->name('detach-action');

        Route::post('/{id}/resource-actions', [RolesApiController::class, 'attachResourceAction'])->name('attach-resource-action');
        Route::delete('/{id}/resource-actions/{resourceActionId}', [RolesApiController::class, 'detachResourceAction'])->name('detach-resource-action');
    });

    Route::prefix('groups')->name('groups.')->group(function () {
        Route::get('/', [GroupsApiController::class, 'index'])->name('index');
        Route::get('/{id}', [GroupsApiController::class, 'show'])->name('show');
        Route::post('/', [GroupsApiController::class, 'store'])->name('store');
        Route::put('/{id}', [GroupsApiController::class, 'update'])->name('update');
        Route::delete('/{id}', [GroupsApiController::class, 'destroy'])->name('destroy');

        Route::post('/{id}/actions', [GroupsApiController::class, 'attachAction'])->name('attach-action');
        Route::delete('/{id}/actions/{actionId}', [GroupsApiController::class, 'detachAction'])->name('detach-action');

        Route::post('/{id}/roles', [GroupsApiController::class, 'attachRole'])->name('attach-role');
        Route::delete('/{id}/roles/{roleId}', [GroupsApiController::class, 'detachRole'])->name('detach-role');

        Route::post('/{id}/resource-actions', [GroupsApiController::class, 'attachResourceAction'])->name('attach-resource-action');
        Route::delete('/{id}/resource-actions/{resourceActionId}', [GroupsApiController::class, 'detachResourceAction'])->name('detach-resource-action');
    });

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/models', [UsersApiController::class, 'availableModels'])->name('available-models');
        Route::get('/', [UsersApiController::class, 'index'])->name('index');
        Route::get('/{id}', [UsersApiController::class, 'show'])->name('show');

        Route::post('/{id}/actions', [UsersApiController::class, 'attachAction'])->name('attach-action');
        Route::delete('/{id}/actions/{actionId}', [UsersApiController::class, 'detachAction'])->name('detach-action');

        Route::post('/{id}/roles', [UsersApiController::class, 'attachRole'])->name('attach-role');
        Route::delete('/{id}/roles/{roleId}', [UsersApiController::class, 'detachRole'])->name('detach-role');

        Route::post('/{id}/groups', [UsersApiController::class, 'attachGroup'])->name('attach-group');
        Route::delete('/{id}/groups/{groupId}', [UsersApiController::class, 'detachGroup'])->name('detach-group');
    });

    Route::prefix('resource-permissions')->name('resource-permissions.')->group(function () {
        Route::get('/models', [ResourcePermissionsApiController::class, 'availableResourceModels'])->name('available-models');
        Route::get('/resources', [ResourcePermissionsApiController::class, 'availableResources'])->name('available-resources');
        Route::get('/', [ResourcePermissionsApiController::class, 'index'])->name('index');
        Route::get('/{id}', [ResourcePermissionsApiController::class, 'show'])->name('show');

        Route::post('/grant', [ResourcePermissionsApiController::class, 'grant'])->name('grant');
        Route::post('/revoke', [ResourcePermissionsApiController::class, 'revoke'])->name('revoke');
        Route::post('/revoke-all', [ResourcePermissionsApiController::class, 'revokeAll'])->name('revoke-all');

        Route::get('/users-with-access', [ResourcePermissionsApiController::class, 'getUsersWithAccess'])->name('users-with-access');
    });
});
