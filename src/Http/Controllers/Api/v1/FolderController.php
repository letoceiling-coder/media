<?php

namespace LetoceilingCoder\Media\Http\Controllers\Api\v1;

use Illuminate\Routing\Controller;
use LetoceilingCoder\Media\Http\Filters\FolderFilter;
use LetoceilingCoder\Media\Http\Requests\StoreFolderRequest;
use LetoceilingCoder\Media\Http\Requests\UpdateFolderRequest;
use LetoceilingCoder\Media\Http\Resources\FolderResource;
use LetoceilingCoder\Media\Models\Folder;
use LetoceilingCoder\Media\Models\Media;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FolderController extends Controller
{
    public function __construct()
    {
        // Middleware можно настроить через config или напрямую здесь
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $filter = app()->make(FolderFilter::class, ['queryParams' => array_filter($request->all())]);

        $trashFolder = Folder::getTrashFolder();
        $isTrashFolder = false;

        if ($request->has('trash') && $request->get('trash') == '1') {
            $isTrashFolder = true;
        } elseif ($request->has('parent_id')) {
            $parentId = $request->get('parent_id');
            if ($trashFolder && ($parentId == $trashFolder->id || $parentId == 4)) {
                $isTrashFolder = true;
            }
        }

        if ($request->has('paginate') && $request->get('paginate') != 0) {
            if ($isTrashFolder) {
                $query = Folder::withTrashed()->filter($filter);
                $query->whereNotNull('deleted_at');
            } else {
                $query = Folder::filter($filter);
            }
            
            return FolderResource::collection(
                $query->paginate(
                    $request->get('paginate'), 
                    ['*'], 
                    'page', 
                    $request->get('page') ?? 1
                )
            );
        }

        if ($isTrashFolder) {
            $query = Folder::withTrashed()->filter($filter)->with('children');
            $query->whereNotNull('deleted_at');
            
            if ($request->has('parent_id')) {
                $parentId = $request->get('parent_id');
                if ($parentId !== null && $parentId !== 'null' && $parentId !== '' && $parentId != 4) {
                    $query->where('parent_id', $parentId);
                }
            }
        } else {
            $query = Folder::filter($filter)->with('children');
            
            if ($request->has('parent_id')) {
                $parentId = $request->get('parent_id');
                if ($parentId === null || $parentId === 'null' || $parentId === '') {
                    $query->whereNull('parent_id');
                } else {
                    $query->where('parent_id', $parentId);
                }
            } else {
                $query->whereNull('parent_id');
            }
        }

        $query->orderBy('position', 'asc')->orderBy('id', 'asc');

        return FolderResource::collection($query->get());
    }

    public function store(StoreFolderRequest $request): AnonymousResourceCollection
    {
        try {
            $folder = Folder::create($request->validated());

            $query = Folder::with('children')
                ->whereNull('deleted_at')
                ->orderBy('position', 'asc')
                ->orderBy('id', 'asc');
            
            if ($folder->parent_id) {
                $query->where('parent_id', $folder->parent_id);
            } else {
                $query->whereNull('parent_id');
            }

            return FolderResource::collection($query->get());
        } catch (\Exception $e) {
            Log::error('Folder creation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    public function show(string $id): FolderResource
    {
        $folder = Folder::withTrashed()
            ->with(['children', 'parent', 'parent.parent', 'parent.parent.parent', 'files'])
            ->findOrFail($id);
        return new FolderResource($folder);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $folder = Folder::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'parent_id' => 'nullable|exists:folders,id',
            'position' => 'sometimes|integer|min:0'
        ]);

        try {
            $folder->update($validated);
            $folder->load(['children', 'parent']);

            return response()->json([
                'success' => true,
                'message' => 'Папка успешно обновлена',
                'data' => new FolderResource($folder)
            ]);
        } catch (\Exception $e) {
            Log::error('Folder update error', [
                'folder_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка обновления папки'
            ], 500);
        }
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $force = $request->get('force', false);
            
            if ($force) {
                $folder = Folder::withTrashed()->findOrFail($id);
                
                if (!$folder->trashed()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Папка не находится в корзине. Используйте обычное удаление.'
                    ], 422);
                }
                
                DB::beginTransaction();
                
                $permanentlyDeleteFolder = function($folderToDelete) use (&$permanentlyDeleteFolder) {
                    $files = Media::where('folder_id', $folderToDelete->id)->get();
                    foreach ($files as $file) {
                        try {
                            $metadata = $file->metadata ? json_decode($file->metadata, true) : [];
                            $path = $metadata['path'] ?? ($file->disk . '/' . $file->name);
                            
                            if (Storage::disk('public')->exists($path)) {
                                Storage::disk('public')->delete($path);
                            }
                        } catch (\Exception $e) {
                            Log::warning('Error deleting media file', [
                                'file_id' => $file->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                        
                        $file->delete();
                    }
                    
                    $children = Folder::withTrashed()->where('parent_id', $folderToDelete->id)->get();
                    foreach ($children as $child) {
                        $permanentlyDeleteFolder($child);
                    }
                    
                    $folderToDelete->forceDelete();
                };
                
                $permanentlyDeleteFolder($folder);
                
                DB::commit();
                
                Log::info('Folder permanently deleted', [
                    'folder_id' => $id,
                    'folder_name' => $folder->name
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Папка безвозвратно удалена'
                ]);
            }
            
            $folder = Folder::findOrFail($id);

            if ($folder->protected) {
                return response()->json([
                    'success' => false,
                    'message' => 'Нельзя удалить защищенную папку'
                ], 403);
            }

            if ($folder->is_trash) {
                return response()->json([
                    'success' => false,
                    'message' => 'Нельзя удалить корзину'
                ], 403);
            }

            $trashFolder = Folder::getTrashFolder();
            if (!$trashFolder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Корзина не найдена'
                ], 404);
            }

            DB::beginTransaction();

            $moveFolderToTrash = function($folderToDelete) use ($trashFolder, &$moveFolderToTrash) {
                $files = Media::where('folder_id', $folderToDelete->id)->get();
                foreach ($files as $file) {
                    $file->original_folder_id = $file->folder_id;
                    $file->folder_id = $trashFolder->id;
                    $file->deleted_at = now();
                    $file->save();
                }

                $children = Folder::where('parent_id', $folderToDelete->id)->get();
                foreach ($children as $child) {
                    $moveFolderToTrash($child);
                }

                $folderToDelete->delete();
            };

            $moveFolderToTrash($folder);

            DB::commit();

            Log::info('Folder moved to trash', [
                'folder_id' => $folder->id,
                'folder_name' => $folder->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Папка перемещена в корзину'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Folder deletion error', [
                'folder_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка удаления папки: ' . $e->getMessage()
            ], 500);
        }
    }

    public function restore(string $id): JsonResponse
    {
        try {
            $folder = Folder::withTrashed()->findOrFail($id);

            if (!$folder->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Папка не находится в корзине'
                ], 400);
            }

            $trashFolder = Folder::getTrashFolder();
            if (!$trashFolder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Корзина не найдена'
                ], 404);
            }

            DB::beginTransaction();

            $restoreFolderFromTrash = function($folderToRestore) use ($trashFolder, &$restoreFolderFromTrash) {
                $files = Media::where('folder_id', $trashFolder->id)
                    ->where('original_folder_id', $folderToRestore->id)
                    ->get();
                
                foreach ($files as $file) {
                    $file->folder_id = $file->original_folder_id;
                    $file->original_folder_id = null;
                    $file->deleted_at = null;
                    $file->save();
                }

                $children = Folder::withTrashed()
                    ->where('parent_id', $folderToRestore->id)
                    ->whereNotNull('deleted_at')
                    ->get();
                
                foreach ($children as $child) {
                    $restoreFolderFromTrash($child);
                }

                $folderToRestore->deleted_at = null;
                $folderToRestore->save();
            };

            $restoreFolderFromTrash($folder);

            DB::commit();

            Log::info('Folder restored from trash', [
                'folder_id' => $folder->id,
                'folder_name' => $folder->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Папка успешно восстановлена'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Folder restore error', [
                'folder_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка восстановления папки: ' . $e->getMessage()
            ], 500);
        }
    }

    public function tree(Request $request): AnonymousResourceCollection
    {
        $folders = Folder::with('children')
            ->whereNull('parent_id')
            ->whereNull('deleted_at')
            ->orderBy('position', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return FolderResource::collection($folders);
    }

    public function updatePositions(Request $request): JsonResponse
    {
        $request->validate([
            'folders' => 'required|array|min:1',
            'folders.*.id' => 'required|exists:folders,id',
            'folders.*.position' => 'required|integer|min:0'
        ], [
            'folders.required' => 'Массив папок обязателен',
            'folders.array' => 'Папки должны быть переданы в виде массива',
            'folders.*.id.required' => 'ID папки обязателен',
            'folders.*.id.exists' => 'Папка с указанным ID не найдена',
            'folders.*.position.required' => 'Позиция обязательна',
            'folders.*.position.integer' => 'Позиция должна быть целым числом'
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->folders as $folderData) {
                Folder::where('id', $folderData['id'])->update([
                    'position' => $folderData['position']
                ]);
            }

            DB::commit();

            Log::info('Folder positions updated', [
                'count' => count($request->folders),
                'folders' => $request->folders
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Позиции папок успешно обновлены'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Folder positions update error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка обновления позиций: ' . $e->getMessage()
            ], 500);
        }
    }
}
