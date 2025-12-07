<?php

namespace LetoceilingCoder\Media\Http\Controllers\Api\v1;

use Illuminate\Routing\Controller;
use LetoceilingCoder\Media\Http\Requests\StoreMediaRequest;
use LetoceilingCoder\Media\Http\Resources\MediaResource;
use LetoceilingCoder\Media\Models\Media;
use LetoceilingCoder\Media\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MediaController extends Controller
{
    public function __construct()
    {
        // Middleware можно настроить через config или напрямую здесь
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Media::query();

        // Получаем папку корзины для проверки
        $trashFolder = Folder::getTrashFolder();
        $isTrashFolder = false;

        // Фильтрация по папке
        if ($request->has('folder_id')) {
            $folderId = $request->get('folder_id');

            // Проверяем на null, 'null', '' и 0
            if ($folderId === null || $folderId === 'null' || $folderId === '' || $folderId === 0 || $folderId === '0') {
                $query->whereNull('folder_id');
            } else {
                $query->where('folder_id', $folderId);
                
                // Проверяем, является ли запрашиваемая папка корзиной
                if ($trashFolder && ($folderId == $trashFolder->id || $folderId == 4)) {
                    $isTrashFolder = true;
                }
            }
        } else {
            // Если параметр folder_id не передан, показываем только корневые файлы
            $query->whereNull('folder_id');
        }

        // Для корзины показываем все файлы (включая с deleted_at)
        // Для обычных папок исключаем файлы с deleted_at (мягко удаленные)
        if (!$isTrashFolder) {
            $query->whereNull('deleted_at');
        }

        // Фильтрация по original_folder_id (для удаленных папок в корзине)
        if ($request->has('original_folder_id') && $request->get('original_folder_id')) {
            $originalFolderId = $request->get('original_folder_id');
            $query->where('original_folder_id', $originalFolderId);
        }

        // Поиск по имени и расширению
        if ($request->has('search') && $request->get('search')) {
            $search = trim($request->get('search'));
            $query->where(function($q) use ($search) {
                $q->where('original_name', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('extension', 'like', "%{$search}%");
            });
        }

        // Фильтрация по типу файла
        if ($request->has('type') && $request->get('type')) {
            $type = $request->get('type');
            $query->where('type', $type);
        }

        // Фильтрация по расширению
        if ($request->has('extension') && $request->get('extension')) {
            $extension = $request->get('extension');
            $query->where('extension', $extension);
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSortFields = ['name', 'original_name', 'size', 'type', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Пагинация
        $perPage = (int) $request->get('per_page', config('media.pagination.per_page_default', 20));
        $perPageMax = config('media.pagination.per_page_max', 100);
        
        // Ограничиваем максимальное количество на странице
        if ($perPage > $perPageMax) {
            $perPage = $perPageMax;
        }
        
        // Минимум 1 файл на странице
        if ($perPage < 1) {
            $perPage = config('media.pagination.per_page_default', 20);
        }

        return MediaResource::collection(
            $query->paginate($perPage)
        );
    }

    public function store(StoreMediaRequest $request): JsonResponse
    {
        try {
            $file = $request->file('file');
            $folderId = $request->input('folder_id');
            
            // Запрещаем загрузку файлов напрямую в корзину
            if ($folderId) {
                $targetFolder = Folder::find($folderId);
                if ($targetFolder && $targetFolder->is_trash) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Нельзя загружать файлы в корзину.'
                    ], 403);
                }
            }

            // Получаем информацию о файле
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $mimeType = $file->getClientMimeType();
            $fileSize = $file->getSize();

            // Генерируем уникальное имя
            $fileName = uniqid() . '_' . time() . '.' . $extension;

            // Определяем тип файла
            $type = $this->getFileType($mimeType);

            // Определяем путь для сохранения
            $uploadPath = config('media.upload.path', 'upload');
            if ($folderId) {
                $folder = Folder::find($folderId);
                if ($folder) {
                    $folderPath = $this->getFolderPath($folder);
                    $uploadPath = config('media.upload.path', 'upload') . '/' . $folderPath;
                }
            }

            // Создаём директорию если не существует
            $fullPath = public_path($uploadPath);
            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0755, true);
                // Создаем .gitkeep для сохранения структуры в git
                if (!file_exists($fullPath . '/.gitkeep')) {
                    touch($fullPath . '/.gitkeep');
                }
            }

            // Сохраняем файл
            $file->move($fullPath, $fileName);
            $relativePath = $uploadPath . '/' . $fileName;

            // Получаем размеры изображения
            $width = null;
            $height = null;
            if ($type === 'photo') {
                $imagePath = public_path($relativePath);
                $imageInfo = @getimagesize($imagePath);
                if ($imageInfo !== false) {
                    $width = $imageInfo[0];
                    $height = $imageInfo[1];
                }
            }

            // Сохраняем в БД
            $media = Media::create([
                'name' => $fileName,
                'original_name' => $originalName,
                'extension' => $extension,
                'disk' => $uploadPath,
                'width' => $width,
                'height' => $height,
                'type' => $type,
                'size' => $fileSize,
                'folder_id' => $folderId,
                'user_id' => auth()->check() ? auth()->id() : null,
                'temporary' => false,
                'metadata' => json_encode([
                    'path' => $relativePath,
                    'mime_type' => $mimeType
                ])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Файл успешно загружен',
                'data' => new MediaResource($media)
            ]);

        } catch (\Exception $e) {
            Log::error('Media upload error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки файла',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id): MediaResource
    {
        $media = Media::with(['folder', 'user'])->findOrFail($id);
        return new MediaResource($media);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $media = Media::findOrFail($id);

        $request->validate([
            'folder_id' => 'nullable|exists:folders,id',
            'file' => 'nullable|file|max:10240'
        ]);

        try {
            $newFolderId = $request->input('folder_id');
            
            if ($newFolderId === '' || $newFolderId === 'null' || $newFolderId === null) {
                $newFolderId = null;
            } else {
                $newFolderId = (int) $newFolderId;
            }
            $newFile = $request->file('file');
            
            // Если загружен новый файл, заменяем существующий
            if ($newFile) {
                // Удаляем старый файл
                $metadata = $media->metadata ? json_decode($media->metadata, true) : [];
                $oldPath = public_path($metadata['path'] ?? ($media->disk . '/' . $media->name));
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
                
                // Получаем информацию о новом файле
                $originalName = $newFile->getClientOriginalName();
                $extension = $newFile->getClientOriginalExtension();
                $mimeType = $newFile->getClientMimeType();
                $fileSize = $newFile->getSize();
                
                // Генерируем уникальное имя
                $fileName = uniqid() . '_' . time() . '.' . $extension;
                
                // Определяем тип файла
                $type = $this->getFileType($mimeType);
                
                // Определяем путь для сохранения
                $folderIdForPath = $newFolderId !== null ? $newFolderId : $media->folder_id;
                $uploadPath = config('media.upload.path', 'upload');
                if ($folderIdForPath !== null) {
                    $folder = Folder::find($folderIdForPath);
                    if ($folder) {
                        $folderPath = $this->getFolderPath($folder);
                        $uploadPath = config('media.upload.path', 'upload') . '/' . $folderPath;
                    }
                }
                
                // Создаём директорию если не существует
                $fullPath = public_path($uploadPath);
                if (!file_exists($fullPath)) {
                    mkdir($fullPath, 0755, true);
                }
                
                // Сохраняем новый файл
                $newFile->move($fullPath, $fileName);
                $relativePath = $uploadPath . '/' . $fileName;
                
                // Получаем размеры изображения
                $width = null;
                $height = null;
                if ($type === 'photo') {
                    $imagePath = public_path($relativePath);
                    $imageInfo = @getimagesize($imagePath);
                    if ($imageInfo !== false) {
                        $width = $imageInfo[0];
                        $height = $imageInfo[1];
                    }
                }
                
                // Обновляем метаданные
                $metadata = ['path' => $relativePath, 'mime_type' => $mimeType];
                
                // Обновляем запись в БД
                $finalFolderId = $newFolderId !== null ? $newFolderId : $media->folder_id;
                $media->update([
                    'name' => $fileName,
                    'original_name' => $originalName,
                    'extension' => $extension,
                    'disk' => $uploadPath,
                    'width' => $width,
                    'height' => $height,
                    'type' => $type,
                    'size' => $fileSize,
                    'folder_id' => $finalFolderId,
                    'metadata' => json_encode($metadata)
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Файл успешно обновлён',
                    'data' => new MediaResource($media->fresh())
                ]);
            }
            
            // Запрещаем прямое перемещение файлов в корзину
            if ($newFolderId) {
                $targetFolder = Folder::find($newFolderId);
                if ($targetFolder && $targetFolder->is_trash) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Нельзя перемещать файлы в корзину. Используйте функцию удаления.'
                    ], 403);
                }
            }

            // Если папка изменилась, перемещаем физический файл
            $currentFolderId = $media->folder_id;
            if (($newFolderId !== $currentFolderId) || 
                (is_null($newFolderId) && !is_null($currentFolderId)) || 
                (!is_null($newFolderId) && is_null($currentFolderId))) {
                
                $metadata = $media->metadata ? json_decode($media->metadata, true) : [];
                $oldPath = public_path($metadata['path'] ?? ($media->disk . '/' . $media->name));

                // Определяем новый путь
                $newUploadPath = config('media.upload.path', 'upload');
                if ($newFolderId) {
                    $folder = Folder::find($newFolderId);
                    if ($folder) {
                        $folderPath = $this->getFolderPath($folder);
                        $newUploadPath = config('media.upload.path', 'upload') . '/' . $folderPath;
                    }
                }

                // Создаём новую директорию если не существует
                $newFullPath = public_path($newUploadPath);
                if (!file_exists($newFullPath)) {
                    mkdir($newFullPath, 0755, true);
                    // Создаем .gitkeep для сохранения структуры в git
                    if (!file_exists($newFullPath . '/.gitkeep')) {
                        touch($newFullPath . '/.gitkeep');
                    }
                }

                // Перемещаем файл
                $newFilePath = $newFullPath . '/' . $media->name;
                if (file_exists($oldPath)) {
                    rename($oldPath, $newFilePath);
                }

                // Обновляем метаданные в БД
                $metadata['path'] = $newUploadPath . '/' . $media->name;

                $media->update([
                    'folder_id' => $newFolderId,
                    'disk' => $newUploadPath,
                    'metadata' => json_encode($metadata)
                ]);

                Log::info('File moved', [
                    'file' => $media->name,
                    'from' => $oldPath,
                    'to' => $newFilePath
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Файл успешно перемещён',
                'data' => new MediaResource($media)
            ]);

        } catch (\Exception $e) {
            Log::error('Media move error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка перемещения файла',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $media = Media::findOrFail($id);
            
            // Получаем папку корзины
            $trashFolder = Folder::getTrashFolder();
            
            if (!$trashFolder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Корзина не найдена'
                ], 404);
            }

            // Если файл уже в корзине - удаляем физически и из БД
            if ($media->folder_id == $trashFolder->id) {
                $metadata = $media->metadata ? json_decode($media->metadata, true) : [];
                $filePath = public_path($metadata['path'] ?? ($media->disk . '/' . $media->name));

                if (file_exists($filePath) && is_file($filePath)) {
                    unlink($filePath);
                    Log::info('File permanently deleted from filesystem', ['path' => $filePath]);
                }

                $media->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Файл безвозвратно удалён',
                    'permanently_deleted' => true
                ]);
            }

            // Сохраняем оригинальную папку для восстановления
            $media->original_folder_id = $media->folder_id;
            $media->folder_id = $trashFolder->id;
            $media->deleted_at = now();
            $media->save();

            Log::info('File moved to trash', [
                'media_id' => $media->id,
                'from_folder' => $media->original_folder_id,
                'to_trash' => $trashFolder->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Файл перемещён в корзину',
                'moved_to_trash' => true,
                'data' => new MediaResource($media)
            ]);
        } catch (\Exception $e) {
            Log::error('Media deletion error', [
                'media_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка удаления файла'
            ], 500);
        }
    }

    public function restore(string $id): JsonResponse
    {
        try {
            $media = Media::findOrFail($id);
            
            // Проверяем что файл в корзине
            $trashFolder = Folder::getTrashFolder();
            if ($media->folder_id != $trashFolder->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Файл не находится в корзине'
                ], 400);
            }
            
            // Восстанавливаем в оригинальную папку
            $media->folder_id = $media->original_folder_id;
            $media->original_folder_id = null;
            $media->deleted_at = null;
            $media->save();
            
            Log::info('File restored from trash', [
                'media_id' => $media->id,
                'restored_to_folder' => $media->folder_id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Файл успешно восстановлен',
                'data' => new MediaResource($media)
            ]);
        } catch (\Exception $e) {
            Log::error('Media restore error', [
                'media_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка восстановления файла'
            ], 500);
        }
    }
    
    public function emptyTrash(): JsonResponse
    {
        try {
            $trashFolder = Folder::getTrashFolder();
            
            if (!$trashFolder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Корзина не найдена'
                ], 404);
            }
            
            // Получаем все файлы из корзины
            $trashFiles = Media::where('folder_id', $trashFolder->id)->get();
            $deletedCount = 0;
            
            foreach ($trashFiles as $media) {
                $metadata = $media->metadata ? json_decode($media->metadata, true) : [];
                $filePath = public_path($metadata['path'] ?? ($media->disk . '/' . $media->name));
                
                if (file_exists($filePath) && is_file($filePath)) {
                    unlink($filePath);
                }
                
                $media->delete();
                $deletedCount++;
            }
            
            Log::info('Trash emptied', ['deleted_files' => $deletedCount]);
            
            return response()->json([
                'success' => true,
                'message' => "Корзина очищена. Удалено файлов: $deletedCount",
                'deleted_count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            Log::error('Empty trash error', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка очистки корзины'
            ], 500);
        }
    }

    private function getFolderPath(Folder $folder): string
    {
        $path = [];
        $currentFolder = $folder;

        while ($currentFolder) {
            array_unshift($path, Str::slug($currentFolder->name));
            $currentFolder = $currentFolder->parent;
        }

        return implode('/', $path);
    }

    private function getFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'photo';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        return 'document';
    }
}
