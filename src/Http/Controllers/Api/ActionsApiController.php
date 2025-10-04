<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Http\Controllers\Api;

use DFiks\UnPerm\Http\Resources\ActionResource;
use DFiks\UnPerm\Models\Action;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class ActionsApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Action::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $actions = $query->orderBy('slug')->paginate($perPage);

        return ActionResource::collection($actions);
    }

    public function show(string $id): ActionResource
    {
        $action = Action::with(['users', 'roles'])->findOrFail($id);

        return new ActionResource($action);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'required|string|max:255|unique:actions,slug',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $action = Action::create([
            'slug' => $request->input('slug'),
            'description' => $request->input('description'),
        ]);

        return response()->json([
            'message' => 'Action created successfully',
            'data' => new ActionResource($action),
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $action = Action::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'slug' => 'sometimes|string|max:255|unique:actions,slug,' . $id,
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $action->update($request->only(['slug', 'description']));

        return response()->json([
            'message' => 'Action updated successfully',
            'data' => new ActionResource($action->fresh()),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $action = Action::findOrFail($id);
        $action->delete();

        return response()->json([
            'message' => 'Action deleted successfully',
        ]);
    }
}
