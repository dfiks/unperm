<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Http\Controllers\Api;

use DFiks\UnPerm\Http\Resources\ResourceActionResource;
use DFiks\UnPerm\Models\ResourceAction;
use DFiks\UnPerm\Services\ModelDiscovery;
use DFiks\UnPerm\Support\ResourcePermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Schema;

class ResourcePermissionsApiController extends Controller
{
    public function __construct(
        protected ModelDiscovery $modelDiscovery
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ResourceAction::query();

        if ($request->has('resource_type')) {
            $query->where('resource_type', $request->input('resource_type'));
        }

        if ($request->has('resource_id')) {
            $query->where('resource_id', $request->input('resource_id'));
        }

        if ($request->has('action_type')) {
            $query->where('action_type', $request->input('action_type'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('slug', 'like', "%{$search}%")
                    ->orWhere('action_type', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $resourceActions = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return ResourceActionResource::collection($resourceActions);
    }

    public function show(string $id): ResourceActionResource
    {
        $resourceAction = ResourceAction::findOrFail($id);

        return new ResourceActionResource($resourceAction);
    }

    public function grant(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_model' => 'required|string',
            'user_id' => 'required',
            'resource_model' => 'required|string',
            'resource_id' => 'required',
            'action_type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userModel = $request->input('user_model');
        $resourceModel = $request->input('resource_model');

        if (!class_exists($userModel) || !class_exists($resourceModel)) {
            return response()->json([
                'message' => 'Model not found',
            ], 404);
        }

        $user = $userModel::findOrFail($request->input('user_id'));
        $resource = $resourceModel::findOrFail($request->input('resource_id'));

        ResourcePermission::grant($user, $resource, $request->input('action_type'));

        return response()->json([
            'message' => 'Permission granted successfully',
        ]);
    }

    public function revoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_model' => 'required|string',
            'user_id' => 'required',
            'resource_model' => 'required|string',
            'resource_id' => 'required',
            'action_type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userModel = $request->input('user_model');
        $resourceModel = $request->input('resource_model');

        if (!class_exists($userModel) || !class_exists($resourceModel)) {
            return response()->json([
                'message' => 'Model not found',
            ], 404);
        }

        $user = $userModel::findOrFail($request->input('user_id'));
        $resource = $resourceModel::findOrFail($request->input('resource_id'));

        ResourcePermission::revoke($user, $resource, $request->input('action_type'));

        return response()->json([
            'message' => 'Permission revoked successfully',
        ]);
    }

    public function revokeAll(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_model' => 'required|string',
            'user_id' => 'required',
            'resource_model' => 'required|string',
            'resource_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userModel = $request->input('user_model');
        $resourceModel = $request->input('resource_model');

        if (!class_exists($userModel) || !class_exists($resourceModel)) {
            return response()->json([
                'message' => 'Model not found',
            ], 404);
        }

        $user = $userModel::findOrFail($request->input('user_id'));
        $resource = $resourceModel::findOrFail($request->input('resource_id'));

        ResourcePermission::revokeAll($user, $resource);

        return response()->json([
            'message' => 'All permissions revoked successfully',
        ]);
    }

    public function getUsersWithAccess(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'resource_model' => 'required|string',
            'resource_id' => 'required',
            'action_type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $resourceModel = $request->input('resource_model');

        if (!class_exists($resourceModel)) {
            return response()->json([
                'message' => 'Model not found',
            ], 404);
        }

        $resource = $resourceModel::findOrFail($request->input('resource_id'));
        $users = ResourcePermission::getUsersWithAccess($resource, $request->input('action_type'));

        return response()->json([
            'data' => $users,
        ]);
    }

    public function availableResourceModels(): JsonResponse
    {
        $models = $this->modelDiscovery->findModelsWithResourcePermissions();

        return response()->json([
            'data' => array_values($models),
        ]);
    }

    public function availableResources(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'resource_model' => 'required|string',
            'search' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $resourceModel = $request->input('resource_model');

        if (!class_exists($resourceModel)) {
            return response()->json([
                'message' => 'Model not found',
            ], 404);
        }

        $query = $resourceModel::query();

        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            $instance = new $resourceModel();
            $query->where(function ($q) use ($search, $instance) {
                $searchFields = ['name', 'title', 'slug'];
                foreach ($searchFields as $field) {
                    if (Schema::hasColumn($instance->getTable(), $field)) {
                        $q->orWhere($field, 'like', "%{$search}%");
                    }
                }
            });
        }

        $perPage = $request->input('per_page', 15);
        $resources = $query->paginate($perPage);

        return response()->json($resources);
    }
}
