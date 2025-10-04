<?php

namespace App\Http\Controllers\Admin\Permission;

use App\Http\Controllers\Controller;
use DFiks\UnPerm\Http\Concerns\AuthorizesResources;
use DFiks\UnPerm\Services\ActionService;
use DFiks\UnPerm\Services\GroupService;
use DFiks\UnPerm\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Контроллер для управления Groups.
 *
 * Скопируйте этот файл в ваш проект и адаптируйте под свои нужды.
 */
class GroupManagementController extends Controller
{
    use AuthorizesResources;

    public function __construct(
        protected GroupService $groupService,
        protected ActionService $actionService,
        protected RoleService $roleService
    ) {
    }

    /**
     * Список всех групп.
     */
    public function index(Request $request)
    {
        $this->authorizeAction('admin.permissions.view');

        $search = $request->input('search');
        $perPage = $request->input('per_page', 15);

        $groups = $this->groupService->paginate($perPage, $search);

        if ($request->wantsJson()) {
            return response()->json($groups);
        }

        return view('admin.permissions.groups.index', compact('groups', 'search'));
    }

    /**
     * Показать конкретную группу.
     */
    public function show(string $id)
    {
        $this->authorizeAction('admin.permissions.view');

        $group = $this->groupService->find($id);

        if (!$group) {
            abort(404);
        }

        $usersCount = $this->groupService->getUsersCount($group);

        return view('admin.permissions.groups.show', compact('group', 'usersCount'));
    }

    /**
     * Форма создания группы.
     */
    public function create()
    {
        $this->authorizeAction('admin.permissions.manage');

        $allActions = $this->actionService->getAll();
        $allRoles = $this->roleService->getAll();

        return view('admin.permissions.groups.create', compact('allActions', 'allRoles'));
    }

    /**
     * Создать новую группу.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeAction('admin.permissions.manage');

        $validated = $request->validate([
            'slug' => 'required|string|max:255|unique:groups,slug',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'action_ids' => 'nullable|array',
            'action_ids.*' => 'exists:actions,id',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $group = $this->groupService->create($validated);

        return response()->json([
            'message' => 'Группа создана успешно',
            'data' => $group,
        ], 201);
    }

    /**
     * Форма редактирования группы.
     */
    public function edit(string $id)
    {
        $this->authorizeAction('admin.permissions.manage');

        $group = $this->groupService->find($id);

        if (!$group) {
            abort(404);
        }

        $allActions = $this->actionService->getAll();
        $allRoles = $this->roleService->getAll();

        return view('admin.permissions.groups.edit', compact('group', 'allActions', 'allRoles'));
    }

    /**
     * Обновить группу.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $this->authorizeAction('admin.permissions.manage');

        $group = $this->groupService->find($id);

        if (!$group) {
            return response()->json(['message' => 'Группа не найдена'], 404);
        }

        $validated = $request->validate([
            'slug' => 'sometimes|string|max:255|unique:groups,slug,' . $id,
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:500',
            'action_ids' => 'nullable|array',
            'action_ids.*' => 'exists:actions,id',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $group = $this->groupService->update($group, $validated);

        return response()->json([
            'message' => 'Группа обновлена успешно',
            'data' => $group,
        ]);
    }

    /**
     * Удалить группу.
     */
    public function destroy(string $id): JsonResponse
    {
        $this->authorizeAction('admin.permissions.manage');

        $group = $this->groupService->find($id);

        if (!$group) {
            return response()->json(['message' => 'Группа не найдена'], 404);
        }

        // Проверить что группа не используется
        $usersCount = $this->groupService->getUsersCount($group);

        if ($usersCount > 0) {
            return response()->json([
                'message' => 'Невозможно удалить группу, она назначена пользователям',
                'users_count' => $usersCount,
            ], 422);
        }

        $this->groupService->delete($group);

        return response()->json([
            'message' => 'Группа удалена успешно',
        ]);
    }

    /**
     * Добавить action к группе.
     */
    public function attachAction(Request $request, string $id): JsonResponse
    {
        $this->authorizeAction('admin.permissions.manage');

        $group = $this->groupService->find($id);

        if (!$group) {
            return response()->json(['message' => 'Группа не найдена'], 404);
        }

        $validated = $request->validate([
            'action_id' => 'required|exists:actions,id',
        ]);

        $action = $this->actionService->find($validated['action_id']);
        $this->groupService->attachAction($group, $action);

        return response()->json([
            'message' => 'Action добавлен к группе',
            'data' => $group->fresh(['actions', 'roles']),
        ]);
    }

    /**
     * Добавить role к группе.
     */
    public function attachRole(Request $request, string $id): JsonResponse
    {
        $this->authorizeAction('admin.permissions.manage');

        $group = $this->groupService->find($id);

        if (!$group) {
            return response()->json(['message' => 'Группа не найдена'], 404);
        }

        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $role = $this->roleService->find($validated['role_id']);
        $this->groupService->attachRole($group, $role);

        return response()->json([
            'message' => 'Роль добавлена к группе',
            'data' => $group->fresh(['actions', 'roles']),
        ]);
    }

    /**
     * Синхронизировать групп из конфигурации.
     */
    public function sync(): JsonResponse
    {
        if (!$this->isSuperAdmin()) {
            return $this->forbiddenResponse('Только супер-админ может синхронизировать группы');
        }

        $groups = config('unperm.groups', []);
        $this->groupService->sync($groups);

        return response()->json([
            'message' => 'Группы синхронизированы успешно',
            'count' => count($groups),
        ]);
    }
}
