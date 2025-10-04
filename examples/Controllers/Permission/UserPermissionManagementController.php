<?php

namespace App\Http\Controllers\Admin\Permission;

use App\Http\Controllers\Controller;
use DFiks\UnPerm\Http\Concerns\AuthorizesResources;
use DFiks\UnPerm\Services\ActionService;
use DFiks\UnPerm\Services\GroupService;
use DFiks\UnPerm\Services\RoleService;
use DFiks\UnPerm\Services\UserPermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Контроллер для управления разрешениями пользователей.
 *
 * Скопируйте этот файл в ваш проект и адаптируйте под свои нужды.
 */
class UserPermissionManagementController extends Controller
{
    use AuthorizesResources;

    public function __construct(
        protected UserPermissionService $userPermissionService,
        protected ActionService $actionService,
        protected RoleService $roleService,
        protected GroupService $groupService
    ) {
    }

    /**
     * Список пользователей.
     */
    public function index(Request $request)
    {
        $this->authorizeAction('admin.users.view');

        $modelClass = $request->input('model', \App\Models\User::class);
        $search = $request->input('search');
        $perPage = $request->input('per_page', 15);

        $users = $this->userPermissionService->getUsers($modelClass, $perPage, $search);

        if ($request->wantsJson()) {
            return response()->json($users);
        }

        $availableModels = $this->userPermissionService->getAvailableUserModels();

        return view('admin.permissions.users.index', compact(
            'users',
            'search',
            'modelClass',
            'availableModels'
        ));
    }

    /**
     * Показать разрешения пользователя.
     */
    public function show(Request $request, string $id)
    {
        $this->authorizeAnyAction(['admin.users.view', 'admin.permissions.view']);

        $modelClass = $request->input('model', \App\Models\User::class);
        $user = $this->userPermissionService->getUser($modelClass, $id);

        if (!$user) {
            abort(404);
        }

        $permissions = $this->userPermissionService->getAllPermissions($user);

        if ($request->wantsJson()) {
            return response()->json($permissions);
        }

        return view('admin.permissions.users.show', compact('user', 'permissions'));
    }

    /**
     * Форма редактирования разрешений пользователя.
     */
    public function edit(Request $request, string $id)
    {
        $this->authorizeAction('admin.users.manage');

        $modelClass = $request->input('model', \App\Models\User::class);
        $user = $this->userPermissionService->getUser($modelClass, $id);

        if (!$user) {
            abort(404);
        }

        $allActions = $this->actionService->getAll();
        $allRoles = $this->roleService->getAll();
        $allGroups = $this->groupService->getAll();

        return view('admin.permissions.users.edit', compact(
            'user',
            'allActions',
            'allRoles',
            'allGroups'
        ));
    }

    /**
     * Назначить action пользователю.
     */
    public function assignAction(Request $request, string $id): JsonResponse
    {
        $this->authorizeAction('admin.users.manage');

        $validated = $request->validate([
            'model' => 'required|string',
            'action_id' => 'required|exists:actions,id',
        ]);

        $user = $this->userPermissionService->getUser($validated['model'], $id);

        if (!$user) {
            return response()->json(['message' => 'Пользователь не найден'], 404);
        }

        $action = $this->actionService->find($validated['action_id']);
        $this->userPermissionService->assignAction($user, $action);

        return response()->json([
            'message' => 'Action назначен пользователю',
            'data' => $user->fresh(['actions']),
        ]);
    }

    /**
     * Убрать action у пользователя.
     */
    public function removeAction(Request $request, string $userId, string $actionId): JsonResponse
    {
        $this->authorizeAction('admin.users.manage');

        $validated = $request->validate([
            'model' => 'required|string',
        ]);

        $user = $this->userPermissionService->getUser($validated['model'], $userId);
        $action = $this->actionService->find($actionId);

        if (!$user || !$action) {
            return response()->json(['message' => 'Пользователь или action не найдены'], 404);
        }

        $this->userPermissionService->removeAction($user, $action);

        return response()->json([
            'message' => 'Action убран у пользователя',
            'data' => $user->fresh(['actions']),
        ]);
    }

    /**
     * Назначить роль пользователю.
     */
    public function assignRole(Request $request, string $id): JsonResponse
    {
        $this->authorizeAction('admin.users.manage');

        $validated = $request->validate([
            'model' => 'required|string',
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = $this->userPermissionService->getUser($validated['model'], $id);

        if (!$user) {
            return response()->json(['message' => 'Пользователь не найден'], 404);
        }

        $role = $this->roleService->find($validated['role_id']);
        $this->userPermissionService->assignRole($user, $role);

        return response()->json([
            'message' => 'Роль назначена пользователю',
            'data' => $user->fresh(['roles']),
        ]);
    }

    /**
     * Убрать роль у пользователя.
     */
    public function removeRole(Request $request, string $userId, string $roleId): JsonResponse
    {
        $this->authorizeAction('admin.users.manage');

        $validated = $request->validate([
            'model' => 'required|string',
        ]);

        $user = $this->userPermissionService->getUser($validated['model'], $userId);
        $role = $this->roleService->find($roleId);

        if (!$user || !$role) {
            return response()->json(['message' => 'Пользователь или роль не найдены'], 404);
        }

        $this->userPermissionService->removeRole($user, $role);

        return response()->json([
            'message' => 'Роль убрана у пользователя',
            'data' => $user->fresh(['roles']),
        ]);
    }

    /**
     * Назначить группу пользователю.
     */
    public function assignGroup(Request $request, string $id): JsonResponse
    {
        $this->authorizeAction('admin.users.manage');

        $validated = $request->validate([
            'model' => 'required|string',
            'group_id' => 'required|exists:groups,id',
        ]);

        $user = $this->userPermissionService->getUser($validated['model'], $id);

        if (!$user) {
            return response()->json(['message' => 'Пользователь не найден'], 404);
        }

        $group = $this->groupService->find($validated['group_id']);
        $this->userPermissionService->assignGroup($user, $group);

        return response()->json([
            'message' => 'Группа назначена пользователю',
            'data' => $user->fresh(['groups']),
        ]);
    }

    /**
     * Убрать группу у пользователя.
     */
    public function removeGroup(Request $request, string $userId, string $groupId): JsonResponse
    {
        $this->authorizeAction('admin.users.manage');

        $validated = $request->validate([
            'model' => 'required|string',
        ]);

        $user = $this->userPermissionService->getUser($validated['model'], $userId);
        $group = $this->groupService->find($groupId);

        if (!$user || !$group) {
            return response()->json(['message' => 'Пользователь или группа не найдены'], 404);
        }

        $this->userPermissionService->removeGroup($user, $group);

        return response()->json([
            'message' => 'Группа убрана у пользователя',
            'data' => $user->fresh(['groups']),
        ]);
    }

    /**
     * Синхронизировать все разрешения пользователя.
     */
    public function syncPermissions(Request $request, string $id): JsonResponse
    {
        $this->authorizeAction('admin.users.manage');

        $validated = $request->validate([
            'model' => 'required|string',
            'action_ids' => 'nullable|array',
            'action_ids.*' => 'exists:actions,id',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'exists:groups,id',
        ]);

        $user = $this->userPermissionService->getUser($validated['model'], $id);

        if (!$user) {
            return response()->json(['message' => 'Пользователь не найден'], 404);
        }

        if (isset($validated['action_ids'])) {
            $this->userPermissionService->syncActions($user, $validated['action_ids']);
        }

        if (isset($validated['role_ids'])) {
            $this->userPermissionService->syncRoles($user, $validated['role_ids']);
        }

        if (isset($validated['group_ids'])) {
            $this->userPermissionService->syncGroups($user, $validated['group_ids']);
        }

        return response()->json([
            'message' => 'Разрешения синхронизированы',
            'data' => $user->fresh(['actions', 'roles', 'groups']),
        ]);
    }

    /**
     * Массовое назначение роли пользователям.
     */
    public function bulkAssignRole(Request $request): JsonResponse
    {
        $this->authorizeAction('admin.users.manage');

        $validated = $request->validate([
            'model' => 'required|string',
            'user_ids' => 'required|array',
            'user_ids.*' => 'string',
            'role_id' => 'required|exists:roles,id',
        ]);

        $role = $this->roleService->find($validated['role_id']);
        $count = $this->userPermissionService->bulkAssignRole(
            $validated['user_ids'],
            $validated['model'],
            $role
        );

        return response()->json([
            'message' => "Роль назначена {$count} пользователям",
            'count' => $count,
        ]);
    }

    /**
     * Массовое назначение группы пользователям.
     */
    public function bulkAssignGroup(Request $request): JsonResponse
    {
        $this->authorizeAction('admin.users.manage');

        $validated = $request->validate([
            'model' => 'required|string',
            'user_ids' => 'required|array',
            'user_ids.*' => 'string',
            'group_id' => 'required|exists:groups,id',
        ]);

        $group = $this->groupService->find($validated['group_id']);
        $count = $this->userPermissionService->bulkAssignGroup(
            $validated['user_ids'],
            $validated['model'],
            $group
        );

        return response()->json([
            'message' => "Группа назначена {$count} пользователям",
            'count' => $count,
        ]);
    }
}
