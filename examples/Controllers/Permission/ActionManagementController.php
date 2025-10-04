<?php

namespace App\Http\Controllers\Admin\Permission;

use App\Http\Controllers\Controller;
use DFiks\UnPerm\Http\Concerns\AuthorizesResources;
use DFiks\UnPerm\Services\ActionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Контроллер для управления Actions.
 *
 * Скопируйте этот файл в ваш проект и адаптируйте под свои нужды.
 */
class ActionManagementController extends Controller
{
    use AuthorizesResources;

    public function __construct(
        protected ActionService $actionService
    ) {
    }

    /**
     * Список всех actions.
     */
    public function index(Request $request)
    {
        $this->authorizeAction('admin.permissions.view');

        $search = $request->input('search');
        $perPage = $request->input('per_page', 15);

        $actions = $this->actionService->paginate($perPage, $search);

        // Для JSON API
        if ($request->wantsJson()) {
            return response()->json($actions);
        }

        // Для веб-интерфейса
        return view('admin.permissions.actions.index', compact('actions', 'search'));
    }

    /**
     * Показать конкретный action.
     */
    public function show(string $id)
    {
        $this->authorizeAction('admin.permissions.view');

        $action = $this->actionService->find($id);

        if (!$action) {
            abort(404);
        }

        $usersCount = $this->actionService->getUsersCount($action);
        $rolesCount = $this->actionService->getRolesCount($action);
        $groupsCount = $this->actionService->getGroupsCount($action);

        return view('admin.permissions.actions.show', compact(
            'action',
            'usersCount',
            'rolesCount',
            'groupsCount'
        ));
    }

    /**
     * Форма создания action.
     */
    public function create()
    {
        $this->authorizeAction('admin.permissions.manage');

        return view('admin.permissions.actions.create');
    }

    /**
     * Создать новый action.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeAction('admin.permissions.manage');

        $validated = $request->validate([
            'slug' => 'required|string|max:255|unique:actions,slug',
            'description' => 'nullable|string|max:500',
        ]);

        $action = $this->actionService->create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Action создан успешно',
                'data' => $action,
            ], 201);
        }

        return response()->json([
            'redirect' => route('admin.permissions.actions.index'),
            'message' => 'Action создан успешно',
        ]);
    }

    /**
     * Форма редактирования action.
     */
    public function edit(string $id)
    {
        $this->authorizeAction('admin.permissions.manage');

        $action = $this->actionService->find($id);

        if (!$action) {
            abort(404);
        }

        return view('admin.permissions.actions.edit', compact('action'));
    }

    /**
     * Обновить action.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $this->authorizeAction('admin.permissions.manage');

        $action = $this->actionService->find($id);

        if (!$action) {
            return response()->json(['message' => 'Action не найден'], 404);
        }

        $validated = $request->validate([
            'slug' => 'sometimes|string|max:255|unique:actions,slug,' . $id,
            'description' => 'nullable|string|max:500',
        ]);

        $action = $this->actionService->update($action, $validated);

        return response()->json([
            'message' => 'Action обновлен успешно',
            'data' => $action,
        ]);
    }

    /**
     * Удалить action.
     */
    public function destroy(string $id): JsonResponse
    {
        $this->authorizeAction('admin.permissions.manage');

        $action = $this->actionService->find($id);

        if (!$action) {
            return response()->json(['message' => 'Action не найден'], 404);
        }

        // Проверить что action не используется
        $usersCount = $this->actionService->getUsersCount($action);
        $rolesCount = $this->actionService->getRolesCount($action);
        $groupsCount = $this->actionService->getGroupsCount($action);

        if ($usersCount > 0 || $rolesCount > 0 || $groupsCount > 0) {
            return response()->json([
                'message' => 'Невозможно удалить action, он используется',
                'details' => [
                    'users' => $usersCount,
                    'roles' => $rolesCount,
                    'groups' => $groupsCount,
                ],
            ], 422);
        }

        $this->actionService->delete($action);

        return response()->json([
            'message' => 'Action удален успешно',
        ]);
    }

    /**
     * Синхронизировать actions из конфигурации.
     */
    public function sync(): JsonResponse
    {
        if (!$this->isSuperAdmin()) {
            return $this->forbiddenResponse('Только супер-админ может синхронизировать actions');
        }

        $actions = config('unperm.actions', []);
        $this->actionService->sync($actions);

        return response()->json([
            'message' => 'Actions синхронизированы успешно',
            'count' => count($actions),
        ]);
    }
}
