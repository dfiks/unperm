<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Http\Controllers\Api;

use DFiks\UnPerm\Http\Resources\RoleResource;
use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\ResourceAction;
use DFiks\UnPerm\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class RolesApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Role::with(['actions', 'resourceActions']);

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('slug', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $roles = $query->orderBy('name')->paginate($perPage);

        return RoleResource::collection($roles);
    }

    public function show(string $id): RoleResource
    {
        $role = Role::with(['actions', 'resourceActions'])->findOrFail($id);

        return new RoleResource($role);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'required|string|max:255|unique:roles,slug',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = Role::create([
            'slug' => $request->input('slug'),
            'name' => $request->input('name'),
            'description' => $request->input('description'),
        ]);

        return response()->json([
            'message' => 'Role created successfully',
            'data' => new RoleResource($role),
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'slug' => 'sometimes|string|max:255|unique:roles,slug,' . $id,
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $role->update($request->only(['slug', 'name', 'description']));

        return response()->json([
            'message' => 'Role updated successfully',
            'data' => new RoleResource($role->fresh(['actions', 'resourceActions'])),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully',
        ]);
    }

    public function attachAction(Request $request, string $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'action_id' => 'required|exists:actions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $action = Action::findOrFail($request->input('action_id'));
        $role->actions()->syncWithoutDetaching([$action->id]);

        return response()->json([
            'message' => 'Action attached to role successfully',
            'data' => new RoleResource($role->fresh(['actions', 'resourceActions'])),
        ]);
    }

    public function detachAction(string $id, string $actionId): JsonResponse
    {
        $role = Role::findOrFail($id);
        $role->actions()->detach($actionId);

        return response()->json([
            'message' => 'Action detached from role successfully',
            'data' => new RoleResource($role->fresh(['actions', 'resourceActions'])),
        ]);
    }

    public function attachResourceAction(Request $request, string $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'resource_action_id' => 'required|exists:resource_actions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $resourceAction = ResourceAction::findOrFail($request->input('resource_action_id'));
        $role->resourceActions()->syncWithoutDetaching([$resourceAction->id]);

        return response()->json([
            'message' => 'Resource action attached to role successfully',
            'data' => new RoleResource($role->fresh(['actions', 'resourceActions'])),
        ]);
    }

    public function detachResourceAction(string $id, string $resourceActionId): JsonResponse
    {
        $role = Role::findOrFail($id);
        $role->resourceActions()->detach($resourceActionId);

        return response()->json([
            'message' => 'Resource action detached from role successfully',
            'data' => new RoleResource($role->fresh(['actions', 'resourceActions'])),
        ]);
    }
}
