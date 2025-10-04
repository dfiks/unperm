<?php

use DFiks\UnPerm\Http\Controllers\UnPermDashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('unperm')
    ->name('unperm.')
    ->middleware(['web'])
    ->group(function () {
        Route::get('/', [UnPermDashboardController::class, 'index'])->name('dashboard');
        Route::get('/actions', [UnPermDashboardController::class, 'actions'])->name('actions');
        Route::get('/roles', [UnPermDashboardController::class, 'roles'])->name('roles');
        Route::get('/groups', [UnPermDashboardController::class, 'groups'])->name('groups');
        Route::get('/users', [UnPermDashboardController::class, 'users'])->name('users');
        Route::get('/resources', [UnPermDashboardController::class, 'resources'])->name('resources');
    });
