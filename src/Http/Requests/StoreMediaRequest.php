<?php

namespace LetoceilingCoder\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Авторизация проверяется через middleware auth:api
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxSize = config('media.upload.max_size', 10240); // По умолчанию 10 МБ
        $allowAllTypes = config('media.upload.allow_all_types', false);
        
        $rules = [
            'file' => ['required', 'file', "max:{$maxSize}"],
            'folder_id' => 'nullable|exists:folders,id'
        ];

        // Если не разрешены все типы, добавляем проверку MIME типов
        if (!$allowAllTypes) {
            $allowedMimes = config('media.upload.allowed_mime_types', []);
            if (!empty($allowedMimes)) {
                $rules['file'][] = 'mimes:' . implode(',', array_map(function($mime) {
                    // Преобразуем MIME типы в расширения для валидации
                    $mimeToExt = [
                        'image/jpeg' => 'jpeg,jpg',
                        'image/png' => 'png',
                        'image/gif' => 'gif',
                        'image/webp' => 'webp',
                        'image/svg+xml' => 'svg',
                        'video/mp4' => 'mp4',
                        'video/avi' => 'avi',
                        'video/mov' => 'mov',
                        'application/pdf' => 'pdf',
                        'application/msword' => 'doc',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                    ];
                    return $mimeToExt[$mime] ?? str_replace(['/', '+'], ['_', '_'], $mime);
                }, $allowedMimes));
            }
        }

        return $rules;
    }
    
    /**
     * Настройка сообщений об ошибках
     */
    public function messages(): array
    {
        $maxSizeMB = round(config('media.upload.max_size', 10240) / 1024, 1);
        
        return [
            'file.required' => 'Файл обязателен для загрузки',
            'file.file' => 'Загружаемый объект должен быть файлом',
            'file.max' => "Размер файла не должен превышать {$maxSizeMB} МБ",
            'file.mimes' => 'Тип файла не разрешен для загрузки',
            'folder_id.exists' => 'Указанная папка не существует'
        ];
    }
}
