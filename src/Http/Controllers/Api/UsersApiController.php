<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Http\Controllers\Api;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Group;
use DFiks\UnPerm\Models\Role;
use DFiks\UnPerm\Services\ModelDiscovery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Schema;

class UsersApiController extends Controller
{
    public function __construct(
        protected ModelDiscovery $modelDiscovery
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'model' => 'required|string',
            'search' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $modelClass = $request->input('model');

        if (!class_exists($modelClass)) {
            return response()->json([
                'message' => 'Model not found',
            ], 404);
        }

        $query = $modelClass::query();

        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $searchFields = ['name', 'email', 'username', 'title', 'slug'];
                foreach ($searchFields as $field) {
                    if (Schema::hasColumn((new $this())->getTable(), $field)) {
                        $q->orWhere($field, 'like', "%{$search}%");
                    }
                }
            });
        }

        $perPage = $request->input('per_page', 15);
        $users = $query->paginate($perPage);

        return response()->json($users);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'model' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $modelClass = $request->input('model');

        if (!class_exists($modelClass)) {
            return response()->json([
                'message' => 'Model not found',
            ], 404);
        }

        $user = $modelClass::with(['actions', 'roles', 'groups', 'resourceActions'])->findOrFail($id);

        return response()->json([
            'data' => $user,
        ]);
    }

    public function attachAction(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'model' => 'required|string',
            'action_id' => 'required|exists:actions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $modelClass = $request->input('model');
        $user = $modelClass::findOrFail($id);
        $action = Action::findOrFail($request->input('action_id'));

        $user->assignAction($action);

        return response()->json([
            'message' => 'Action assigned to user successfully',
            'data' => $user->fresh(['actions', 'roles', 'groups']),
        ]);
    }

    public function detachAction(Request $request, string $id, string $actionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'model' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $modelClass = $request->input('model');
        $user = $modelClass::findOrFail($id);
        $action = Action::findOrFail($actionId);

        $user->removeAction($action);

        return response()->json([
            'message' => 'Action removed from user successfully',
            'data' => $user->fresh(['actions', 'roles', 'groups']),
        ]);
    }

    public function attachRole(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'model' => 'required|string',
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $modelClass = $request->input('model');
        $user = $modelClass::findOrFail($id);
        $role = Role::findOrFail($request->input('role_id'));

        $user->assignRole($role);

        return response()->json([
            'message' => 'Role assigned to user successfully',
            'data' => $user->fresh(['actions', 'roles', 'groups']),
        ]);
    }

    public function detachRole(Request $request, string $id, string $roleId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'model' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $modelClass = $request->input('model');
        $user = $modelClass::findOrFail($id);
        $role = Role::findOrFail($roleId);

        $user->removeRole($role);

        return response()->json([
            'message' => 'Role removed from user successfully',
            'data' => $user->fresh(['actions', 'roles', 'groups']),
        ]);
    }

    public function attachGroup(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'model' => 'required|string',
            'group_id' => 'required|exists:groups,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $modelClass = $request->input('model');
        $user = $modelClass::findOrFail($id);
        $group = Group::findOrFail($request->input('group_id'));

        $user->assignGroup($group);

        return response()->json([
            'message' => 'Group assigned to user successfully',
            'data' => $user->fresh(['actions', 'roles', 'groups']),
        ]);
    }

    public function detachGroup(Request $request, string $id, string $groupId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'model' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $modelClass = $request->input('model');
        $user = $modelClass::findOrFail($id);
        $group = Group::findOrFail($groupId);

        $user->removeGroup($group);

        return response()->json([
            'message' => 'Group removed from user successfully',
            'data' => $user->fresh(['actions', 'roles', 'groups']),
        ]);
    }

    public function availableModels(): JsonResponse
    {
        $models = $this->modelDiscovery->findModelsWithPermissions();

        return response()->json([
            'data' => array_values($models),
        ]);
    }
}
