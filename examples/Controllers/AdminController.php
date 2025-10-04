<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use DFiks\UnPerm\Http\Concerns\AuthorizesResources;
use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Group;
use DFiks\UnPerm\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Пример контроллера админ-панели с использованием UnPerm.
 */
class AdminController extends Controller
{
    use AuthorizesResources;

    /**
     * Список пользователей (только для админов).
     */
    public function users(Request $request): JsonResponse
    {
        $this->authorizeAction('admin.users.view');

        $users = User::with(['actions', 'roles', 'groups'])
            ->paginate(50);

        return response()->json($users);
    }

    /**
     * Назначить роль пользователю.
     */
    public function assignRole(Request $request, User $user): JsonResponse
    {
        $this->authorizeAction('admin.users.manage');

        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $user->assignRole($role);

        return response()->json([
            'message' => 'Role assigned successfully',
            'data' => $user->fresh(['roles']),
        ]);
    }

    /**
     * Убрать роль у пользователя.
     */
    public function removeRole(Request $request, User $user, Role $role): JsonResponse
    {
        $this->authorizeAction('admin.users.manage');

        $user->removeRole($role);

        return response()->json([
            'message' => 'Role removed successfully',
            'data' => $user->fresh(['roles']),
        ]);
    }

    /**
     * Назначить группу пользователю.
     */
    public function assignGroup(Request $request, User $user): JsonResponse
    {
        $this->authorizeAction('admin.users.manage');

        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
        ]);

        $group = Group::findOrFail($validated['group_id']);
        $user->assignGroup($group);

        return response()->json([
            'message' => 'Group assigned successfully',
            'data' => $user->fresh(['groups']),
        ]);
    }

    /**
     * Назначить прямое действие пользователю.
     */
    public function assignAction(Request $request, User $user): JsonResponse
    {
        $this->authorizeAction('admin.users.manage');

        $validated = $request->validate([
            'action_id' => 'required|exists:actions,id',
        ]);

        $action = Action::findOrFail($validated['action_id']);
        $user->assignAction($action);

        return response()->json([
            'message' => 'Action assigned successfully',
            'data' => $user->fresh(['actions']),
        ]);
    }

    /**
     * Получить все разрешения пользователя.
     */
    public function userPermissions(User $user): JsonResponse
    {
        $this->authorizeAnyAction(['admin.users.view', 'admin.users.manage']);

        return response()->json([
            'direct_actions' => $user->actions,
            'roles' => $user->roles->load('actions'),
            'groups' => $user->groups->load(['actions', 'roles']),
            'resource_actions' => $user->resourceActions,
            'is_superadmin' => $user->isSuperAdmin(),
        ]);
    }

    /**
     * Массовое назначение ролей.
     */
    public function bulkAssignRole(Request $request): JsonResponse
    {
        $this->authorizeAction('admin.users.manage');

        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'role_id' => 'required|exists:roles,id',
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $users = User::whereIn('id', $validated['user_ids'])->get();

        foreach ($users as $user) {
            $user->assignRole($role);
        }

        return response()->json([
            'message' => 'Role assigned to ' . $users->count() . ' users successfully',
        ]);
    }

    /**
     * Создать новую роль (только супер-админ).
     */
    public function createRole(Request $request): JsonResponse
    {
        if (!$this->isSuperAdmin()) {
            return $this->forbiddenResponse('Only super admin can create roles');
        }

        $validated = $request->validate([
            'slug' => 'required|string|unique:roles,slug',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'action_ids' => 'nullable|array',
            'action_ids.*' => 'exists:actions,id',
        ]);

        $role = Role::create([
            'slug' => $validated['slug'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        if (!empty($validated['action_ids'])) {
            $role->actions()->sync($validated['action_ids']);
        }

        return response()->json([
            'message' => 'Role created successfully',
            'data' => $role->load('actions'),
        ], 201);
    }
}
