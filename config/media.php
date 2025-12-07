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

    /*
    |--------------------------------------------------------------------------
    | Middleware Settings
    |--------------------------------------------------------------------------
    |
    | Настройки middleware для API роутов
    |
    */

    'middleware' => [
        // Middleware для защиты API роутов
        // Автоматически определяется, если не указано
        // Возможные значения: 'auth:sanctum', 'auth:api', null, или массив middleware
        'api' => env('MEDIA_MIDDLEWARE_API', null),

        // Дополнительные middleware (будут добавлены к основным)
        'additional' => env('MEDIA_MIDDLEWARE_ADDITIONAL', '') ? explode(',', env('MEDIA_MIDDLEWARE_ADDITIONAL', '')) : [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Setup
    |--------------------------------------------------------------------------
    |
    | Автоматическая настройка при установке пакета
    |
    */

    'auto_setup' => [
        // Автоматически создавать директорию для загрузки, если она не существует
        'create_upload_directory' => env('MEDIA_AUTO_CREATE_UPLOAD_DIR', true),

        // Автоматически публиковать конфигурацию при первой установке
        'auto_publish_config' => env('MEDIA_AUTO_PUBLISH_CONFIG', false),
    ],
];
