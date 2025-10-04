<?php

namespace App\Http\Controllers\Admin\Permission;

use App\Http\Controllers\Controller;
use DFiks\UnPerm\Http\Concerns\AuthorizesResources;
use DFiks\UnPerm\Services\ActionService;
use DFiks\UnPerm\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Контроллер для управления Roles.
 *
 * Скопируйте этот файл в ваш проект и адаптируйте под свои нужды.
 */
class RoleManagementController extends Controller
{
    use AuthorizesResources;

    public function __construct(
        protected RoleService $roleService,
        protected ActionService $actionService
    ) {
    }

    /**
     * Список всех ролей.
     */
    public function index(Request $request)
    {
        $this->authorizeAction('admin.permissions.view');

        $search = $request->input('search');
        $perPage = $request->input('per_page', 15);

        $roles = $this->roleService->paginate($perPage, $search);

        if ($request->wantsJson()) {
            return response()->json($roles);
        }

        return view('admin.permissions.roles.index', compact('roles', 'search'));
    }

    /**
     * Показать конкретную роль.
     */
    public function show(string $id)
    {
        $this->authorizeAction('admin.permissions.view');

        $role = $this->roleService->find($id);

        if (!$role) {
            abort(404);
        }

        $usersCount = $this->roleService->getUsersCount($role);

        return view('admin.permissions.roles.show', compact('role', 'usersCount'));
    }

    /**
     * Форма создания роли.
     */
    public function create()
    {
        $this->authorizeAction('admin.permissions.manage');

        $allActions = $this->actionService->getAll();

        return view('admin.permissions.roles.create', compact('allActions'));
    }

    /**
     * Создать новую роль.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeAction('admin.permissions.manage');

        $validated = $request->validate([
            'slug' => 'required|string|max:255|unique:roles,slug',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'action_ids' => 'nullable|array',
            'action_ids.*' => 'exists:actions,id',
        ]);

        $role = $this->roleService->create($validated);

        return response()->json([
            'message' => 'Роль создана успешно',
            'data' => $role,
        ], 201);
    }

    /**
     * Форма редактирования роли.
     */
    public function edit(string $id)
    {
        $this->authorizeAction('admin.permissions.manage');

        $role = $this->roleService->find($id);

        if (!$role) {
            abort(404);
        }

        $allActions = $this->actionService->getAll();

        return view('admin.permissions.roles.edit', compact('role', 'allActions'));
    }

    /**
     * Обновить роль.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $this->authorizeAction('admin.permissions.manage');

        $role = $this->roleService->find($id);

        if (!$role) {
            return response()->json(['message' => 'Роль не найдена'], 404);
        }

        $validated = $request->validate([
            'slug' => 'sometimes|string|max:255|unique:roles,slug,' . $id,
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:500',
            'action_ids' => 'nullable|array',
            'action_ids.*' => 'exists:actions,id',
        ]);

        $role = $this->roleService->update($role, $validated);

        return response()->json([
            'message' => 'Роль обновлена успешно',
            'data' => $role,
        ]);
    }

    /**
     * Удалить роль.
     */
    public function destroy(string $id): JsonResponse
    {
        $this->authorizeAction('admin.permissions.manage');

        $role = $this->roleService->find($id);

        if (!$role) {
            return response()->json(['message' => 'Роль не найдена'], 404);
        }

        // Проверить что роль не используется
        $usersCount = $this->roleService->getUsersCount($role);

        if ($usersCount > 0) {
            return response()->json([
                'message' => 'Невозможно удалить роль, она назначена пользователям',
                'users_count' => $usersCount,
            ], 422);
        }

        $this->roleService->delete($role);

        return response()->json([
            'message' => 'Роль удалена успешно',
        ]);
    }

    /**
     * Добавить action к роли.
     */
    public function attachAction(Request $request, string $id): JsonResponse
    {
        $this->authorizeAction('admin.permissions.manage');

        $role = $this->roleService->find($id);

        if (!$role) {
            return response()->json(['message' => 'Роль не найдена'], 404);
        }

        $validated = $request->validate([
            'action_id' => 'required|exists:actions,id',
        ]);

        $action = $this->actionService->find($validated['action_id']);
        $this->roleService->attachAction($role, $action);

        return response()->json([
            'message' => 'Action добавлен к роли',
            'data' => $role->fresh(['actions']),
        ]);
    }

    /**
     * Убрать action у роли.
     */
    public function detachAction(string $roleId, string $actionId): JsonResponse
    {
        $this->authorizeAction('admin.permissions.manage');

        $role = $this->roleService->find($roleId);
        $action = $this->actionService->find($actionId);

        if (!$role || !$action) {
            return response()->json(['message' => 'Роль или action не найдены'], 404);
        }

        $this->roleService->detachAction($role, $action);

        return response()->json([
            'message' => 'Action убран у роли',
            'data' => $role->fresh(['actions']),
        ]);
    }

    /**
     * Синхронизировать actions роли.
     */
    public function syncActions(Request $request, string $id): JsonResponse
    {
        $this->authorizeAction('admin.permissions.manage');

        $role = $this->roleService->find($id);

        if (!$role) {
            return response()->json(['message' => 'Роль не найдена'], 404);
        }

        $validated = $request->validate([
            'action_ids' => 'required|array',
            'action_ids.*' => 'exists:actions,id',
        ]);

        $this->roleService->syncActions($role, $validated['action_ids']);

        return response()->json([
            'message' => 'Actions синхронизированы',
            'data' => $role->fresh(['actions']),
        ]);
    }

    /**
     * Синхронизировать роли из конфигурации.
     */
    public function sync(): JsonResponse
    {
        if (!$this->isSuperAdmin()) {
            return $this->forbiddenResponse('Только супер-админ может синхронизировать роли');
        }

        $roles = config('unperm.roles', []);
        $this->roleService->sync($roles);

        return response()->json([
            'message' => 'Роли синхронизированы успешно',
            'count' => count($roles),
        ]);
    }
}
