<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Http\Controllers\Api;

use DFiks\UnPerm\Http\Resources\GroupResource;
use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Group;
use DFiks\UnPerm\Models\ResourceAction;
use DFiks\UnPerm\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class GroupsApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Group::with(['actions', 'roles', 'resourceActions']);

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('slug', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $groups = $query->orderBy('name')->paginate($perPage);

        return GroupResource::collection($groups);
    }

    public function show(string $id): GroupResource
    {
        $group = Group::with(['actions', 'roles', 'resourceActions'])->findOrFail($id);

        return new GroupResource($group);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'required|string|max:255|unique:groups,slug',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $group = Group::create([
            'slug' => $request->input('slug'),
            'name' => $request->input('name'),
            'description' => $request->input('description'),
        ]);

        return response()->json([
            'message' => 'Group created successfully',
            'data' => new GroupResource($group),
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $group = Group::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'slug' => 'sometimes|string|max:255|unique:groups,slug,' . $id,
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $group->update($request->only(['slug', 'name', 'description']));

        return response()->json([
            'message' => 'Group updated successfully',
            'data' => new GroupResource($group->fresh(['actions', 'roles', 'resourceActions'])),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $group = Group::findOrFail($id);
        $group->delete();

        return response()->json([
            'message' => 'Group deleted successfully',
        ]);
    }

    public function attachAction(Request $request, string $id): JsonResponse
    {
        $group = Group::findOrFail($id);

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
        $group->actions()->syncWithoutDetaching([$action->id]);

        return response()->json([
            'message' => 'Action attached to group successfully',
            'data' => new GroupResource($group->fresh(['actions', 'roles', 'resourceActions'])),
        ]);
    }

    public function detachAction(string $id, string $actionId): JsonResponse
    {
        $group = Group::findOrFail($id);
        $group->actions()->detach($actionId);

        return response()->json([
            'message' => 'Action detached from group successfully',
            'data' => new GroupResource($group->fresh(['actions', 'roles', 'resourceActions'])),
        ]);
    }

    public function attachRole(Request $request, string $id): JsonResponse
    {
        $group = Group::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = Role::findOrFail($request->input('role_id'));
        $group->roles()->syncWithoutDetaching([$role->id]);

        return response()->json([
            'message' => 'Role attached to group successfully',
            'data' => new GroupResource($group->fresh(['actions', 'roles', 'resourceActions'])),
        ]);
    }

    public function detachRole(string $id, string $roleId): JsonResponse
    {
        $group = Group::findOrFail($id);
        $group->roles()->detach($roleId);

        return response()->json([
            'message' => 'Role detached from group successfully',
            'data' => new GroupResource($group->fresh(['actions', 'roles', 'resourceActions'])),
        ]);
    }

    public function attachResourceAction(Request $request, string $id): JsonResponse
    {
        $group = Group::findOrFail($id);

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
        $group->resourceActions()->syncWithoutDetaching([$resourceAction->id]);

        return response()->json([
            'message' => 'Resource action attached to group successfully',
            'data' => new GroupResource($group->fresh(['actions', 'roles', 'resourceActions'])),
        ]);
    }

    public function detachResourceAction(string $id, string $resourceActionId): JsonResponse
    {
        $group = Group::findOrFail($id);
        $group->resourceActions()->detach($resourceActionId);

        return response()->json([
            'message' => 'Resource action detached from group successfully',
            'data' => new GroupResource($group->fresh(['actions', 'roles', 'resourceActions'])),
        ]);
    }
}
