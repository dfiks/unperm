<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Folder;
use DFiks\UnPerm\Http\Concerns\AuthorizesResources;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Пример контроллера для работы с документами с использованием UnPerm.
 */
class DocumentController extends Controller
{
    use AuthorizesResources;

    /**
     * Список документов в папке.
     */
    public function index(Request $request): JsonResponse
    {
        $folderId = $request->input('folder_id');

        $query = $this->getViewableResources(Document::class);

        if ($folderId) {
            $folder = Folder::findOrFail($folderId);
            $this->authorizeResource($folder, 'view');

            $query->where('folder_id', $folderId);
        }

        $documents = $query->with(['folder', 'author'])->paginate(20);

        return response()->json($documents);
    }

    /**
     * Показать документ.
     */
    public function show(Document $document): JsonResponse
    {
        $this->authorizeResource($document, 'view');

        return response()->json([
            'data' => $document->load(['folder', 'author', 'versions']),
        ]);
    }

    /**
     * Создать документ.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeAction('documents.create');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'folder_id' => 'required|exists:folders,id',
        ]);

        $folder = Folder::findOrFail($validated['folder_id']);
        $this->authorizeResource($folder, 'view');

        $document = Document::create([
            ...$validated,
            'author_id' => auth()->id(),
        ]);

        grantResourcePermission(auth()->user(), $document, 'view');
        grantResourcePermission(auth()->user(), $document, 'update');
        grantResourcePermission(auth()->user(), $document, 'delete');

        return response()->json([
            'message' => 'Document created successfully',
            'data' => $document,
        ], 201);
    }

    /**
     * Обновить документ.
     */
    public function update(Request $request, Document $document): JsonResponse
    {
        $this->authorizeResource($document, 'update');

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
        ]);

        $document->update($validated);

        return response()->json([
            'message' => 'Document updated successfully',
            'data' => $document->fresh(),
        ]);
    }

    /**
     * Удалить документ.
     */
    public function destroy(Document $document): JsonResponse
    {
        $this->authorizeResource($document, 'delete');

        $document->delete();

        return response()->json([
            'message' => 'Document deleted successfully',
        ]);
    }

    /**
     * Скачать документ.
     */
    public function download(Document $document)
    {
        $this->authorizeResource($document, 'view');

        return response()->download($document->file_path);
    }
}
