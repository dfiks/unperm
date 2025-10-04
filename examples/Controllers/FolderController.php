<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use DFiks\UnPerm\Http\Concerns\AuthorizesResources;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Пример контроллера для работы с папками с использованием UnPerm.
 */
class FolderController extends Controller
{
    use AuthorizesResources;

    /**
     * Список всех папок доступных текущему пользователю.
     */
    public function index(): JsonResponse
    {
        $folders = $this->getViewableResources(Folder::class)
            ->with(['creator', 'parent'])
            ->paginate(15);

        return response()->json($folders);
    }

    /**
     * Показать конкретную папку.
     */
    public function show(Folder $folder): JsonResponse
    {
        $this->authorizeResource($folder, 'view');

        return response()->json([
            'data' => $folder->load(['creator', 'parent', 'children']),
        ]);
    }

    /**
     * Создать новую папку.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeAction('folders.create');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:folders,id',
            'description' => 'nullable|string',
        ]);

        $folder = Folder::create([
            ...$validated,
            'creator_id' => auth()->id(),
        ]);

        grantResourcePermission(auth()->user(), $folder, 'view');
        grantResourcePermission(auth()->user(), $folder, 'update');
        grantResourcePermission(auth()->user(), $folder, 'delete');

        return response()->json([
            'message' => 'Folder created successfully',
            'data' => $folder,
        ], 201);
    }

    /**
     * Обновить папку.
     */
    public function update(Request $request, Folder $folder): JsonResponse
    {
        $this->authorizeResource($folder, 'update');

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'parent_id' => 'nullable|exists:folders,id',
            'description' => 'nullable|string',
        ]);

        $folder->update($validated);

        return response()->json([
            'message' => 'Folder updated successfully',
            'data' => $folder->fresh(),
        ]);
    }

    /**
     * Удалить папку.
     */
    public function destroy(Folder $folder): JsonResponse
    {
        $this->authorizeResource($folder, 'delete');

        $folder->delete();

        return response()->json([
            'message' => 'Folder deleted successfully',
        ]);
    }

    /**
     * Поделиться папкой с пользователем.
     */
    public function share(Request $request, Folder $folder): JsonResponse
    {
        $this->authorizeResource($folder, 'share');

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'permissions' => 'required|array',
            'permissions.*' => 'in:view,update,delete,share',
        ]);

        $user = \App\Models\User::findOrFail($validated['user_id']);

        foreach ($validated['permissions'] as $permission) {
            $this->grantResourceAccess($user, $folder, $permission);
        }

        return response()->json([
            'message' => 'Folder shared successfully',
        ]);
    }

    /**
     * Отозвать доступ к папке.
     */
    public function unshare(Request $request, Folder $folder): JsonResponse
    {
        $this->authorizeResource($folder, 'share');

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = \App\Models\User::findOrFail($validated['user_id']);

        $this->revokeAllResourceAccess($user, $folder);

        return response()->json([
            'message' => 'Access revoked successfully',
        ]);
    }

    /**
     * Получить пользователей с доступом к папке.
     */
    public function usersWithAccess(Folder $folder): JsonResponse
    {
        $this->authorizeResource($folder, 'view');

        $users = $this->getUsersWithAccess($folder, 'view');

        return response()->json([
            'data' => $users,
        ]);
    }
}
