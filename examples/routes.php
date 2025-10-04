<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FolderController;
use Illuminate\Support\Facades\Route;

/**
 * Примеры маршрутов с использованием UnPerm.
 */

// Публичные маршруты (без авторизации)
Route::get('/', function () {
    return view('welcome');
});

// Аутентифицированные маршруты
Route::middleware(['auth'])->group(function () {

    // Папки - только для пользователей с правами
    Route::prefix('folders')->name('folders.')->group(function () {
        Route::get('/', [FolderController::class, 'index'])->name('index');
        Route::get('/{folder}', [FolderController::class, 'show'])->name('show');
        Route::post('/', [FolderController::class, 'store'])->name('store');
        Route::put('/{folder}', [FolderController::class, 'update'])->name('update');
        Route::delete('/{folder}', [FolderController::class, 'destroy'])->name('destroy');

        // Управление доступом
        Route::post('/{folder}/share', [FolderController::class, 'share'])->name('share');
        Route::post('/{folder}/unshare', [FolderController::class, 'unshare'])->name('unshare');
        Route::get('/{folder}/users', [FolderController::class, 'usersWithAccess'])->name('users');
    });

    // Документы
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/', [DocumentController::class, 'index'])->name('index');
        Route::get('/{document}', [DocumentController::class, 'show'])->name('show');
        Route::post('/', [DocumentController::class, 'store'])->name('store');
        Route::put('/{document}', [DocumentController::class, 'update'])->name('update');
        Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('destroy');
        Route::get('/{document}/download', [DocumentController::class, 'download'])->name('download');
    });

    // Админ-панель - только для админов
    Route::prefix('admin')->name('admin.')->group(function () {

        // Управление пользователями
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [AdminController::class, 'users'])->name('index');
            Route::get('/{user}/permissions', [AdminController::class, 'userPermissions'])->name('permissions');

            // Назначение прав
            Route::post('/{user}/roles', [AdminController::class, 'assignRole'])->name('assign-role');
            Route::delete('/{user}/roles/{role}', [AdminController::class, 'removeRole'])->name('remove-role');
            Route::post('/{user}/groups', [AdminController::class, 'assignGroup'])->name('assign-group');
            Route::post('/{user}/actions', [AdminController::class, 'assignAction'])->name('assign-action');

            // Массовые операции
            Route::post('/bulk/assign-role', [AdminController::class, 'bulkAssignRole'])->name('bulk-assign-role');
        });

        // Управление ролями
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::post('/', [AdminController::class, 'createRole'])->name('create');
        });
    });
});

/**
 * Примеры использования middleware UnPerm.
 */

// Проверка глобального разрешения
Route::get('/users', function () {
    return User::all();
})->middleware(['auth', 'unperm:users.view']);

// Проверка разрешения на ресурс
Route::get('/folders/{folder}/edit', function (Folder $folder) {
    return view('folders.edit', compact('folder'));
})->middleware(['auth', 'unperm:folders,update']);

// Проверка роли
Route::get('/admin/dashboard', function () {
    return view('admin.dashboard');
})->middleware(['auth', function ($request, $next) {
    if (!currentUserHasRole('admin')) {
        abort(403);
    }

    return $next($request);
}]);

/**
 * Примеры использования в маршрутах с замыканиями.
 */

// Проверка прав в замыкании
Route::post('/folders/{folder}/archive', function (Folder $folder) {
    authorizeResource($folder, 'delete');

    $folder->archived = true;
    $folder->save();

    return response()->json(['message' => 'Folder archived']);
})->middleware('auth');

// Фильтрация доступных ресурсов
Route::get('/my-folders', function () {
    $folders = viewableResources(Folder::class)
        ->where('creator_id', auth()->id())
        ->get();

    return response()->json($folders);
})->middleware('auth');

// Проверка супер-админа
Route::delete('/system/reset', function () {
    if (!isSuperadmin()) {
        abort(403, 'Only super admin can reset system');
    }

    // Опасная операция
    return response()->json(['message' => 'System reset']);
})->middleware('auth');
