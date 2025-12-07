<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Media Upload Settings
    |--------------------------------------------------------------------------
    |
    | Настройки для загрузки медиа-файлов
    |
    */

    'upload' => [
        // Максимальный размер файла в KB (по умолчанию 10 MB)
        'max_size' => env('MEDIA_MAX_SIZE', 10240),

        // Разрешить загрузку всех типов файлов
        'allow_all_types' => env('MEDIA_ALLOW_ALL_TYPES', false),

        // Разрешенные MIME типы
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'video/mp4',
            'video/avi',
            'video/mov',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],

        // Путь для загрузки файлов (относительно public)
        'path' => env('MEDIA_UPLOAD_PATH', 'upload'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Settings
    |--------------------------------------------------------------------------
    |
    | Настройки пагинации для списка медиа-файлов
    |
    */

    'pagination' => [
        // Количество элементов на странице по умолчанию
        'per_page_default' => env('MEDIA_PER_PAGE_DEFAULT', 20),

        // Максимальное количество элементов на странице
        'per_page_max' => env('MEDIA_PER_PAGE_MAX', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Scoping
    |--------------------------------------------------------------------------
    |
    | Включить фильтрацию по пользователю (user_id)
    |
    */

    'user_scoping' => env('MEDIA_USER_SCOPING', true),

    /*
    |--------------------------------------------------------------------------
    | Trash Settings
    |--------------------------------------------------------------------------
    |
    | Настройки корзины
    |
    */

    'trash' => [
        // ID папки корзины (если не указан, будет создан автоматически)
        'folder_id' => env('MEDIA_TRASH_FOLDER_ID', null),

        // Имя папки корзины
        'folder_name' => env('MEDIA_TRASH_FOLDER_NAME', 'Корзина'),
    ],
];
