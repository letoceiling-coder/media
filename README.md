# Laravel Media Package

Полнофункциональный пакет для управления медиа-файлами в Laravel приложениях. Включает загрузку файлов, управление папками, корзину, фильтрацию, поиск и многое другое.

## Возможности

- ✅ Загрузка файлов (изображения, видео, документы)
- ✅ Управление папками с иерархической структурой
- ✅ Корзина с возможностью восстановления
- ✅ Фильтрация и поиск файлов
- ✅ Пагинация
- ✅ Сортировка
- ✅ Drag & Drop для папок
- ✅ Vue компонент для фронтенда
- ✅ RESTful API
- ✅ Защищенные папки
- ✅ Мультипользовательская поддержка (scope по user_id)
- ✅ Мягкое удаление
- ✅ Автоматическая генерация превью для изображений

## Требования

- PHP >= 8.2
- Laravel >= 10.0
- MySQL/MariaDB или PostgreSQL

## Установка

### 1. Установка через Composer

Добавьте репозиторий в `composer.json` вашего проекта:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/letoceiling-coder/media.git"
        }
    ],
    "require": {
        "letoceiling-coder/media": "dev-main"
    }
}
```

Или установите напрямую:

```bash
composer require letoceiling-coder/media dev-main
```

### 2. Публикация конфигурации и миграций

```bash
php artisan vendor:publish --provider="LetoceilingCoder\Media\MediaServiceProvider" --tag="media-config"
php artisan vendor:publish --provider="LetoceilingCoder\Media\MediaServiceProvider" --tag="media-migrations"
```

### 3. Запуск миграций

```bash
php artisan migrate
```

### 4. Публикация Vue компонента (опционально)

```bash
php artisan vendor:publish --provider="LetoceilingCoder\Media\MediaServiceProvider" --tag="media-components"
```

### 5. Публикация изображений (иконки папок, системные изображения)

```bash
php artisan vendor:publish --provider="LetoceilingCoder\Media\MediaServiceProvider" --tag="media-assets"
```

## Конфигурация

После публикации конфигурации, настройте файл `config/media.php`:

```php
return [
    'upload' => [
        'max_size' => 10240, // Максимальный размер файла в KB (по умолчанию 10 MB)
        'allow_all_types' => false, // Разрешить загрузку всех типов файлов
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'video/mp4',
            // ...
        ],
        'path' => 'upload', // Путь для загрузки файлов
    ],
    
    'pagination' => [
        'per_page_default' => 20,
        'per_page_max' => 100,
    ],
    
    'user_scoping' => true, // Включить фильтрацию по user_id
];
```

Также можно настроить через переменные окружения в `.env`:

```env
MEDIA_MAX_SIZE=10240
MEDIA_ALLOW_ALL_TYPES=false
MEDIA_UPLOAD_PATH=upload
MEDIA_PER_PAGE_DEFAULT=20
MEDIA_PER_PAGE_MAX=100
MEDIA_USER_SCOPING=true
```

## Использование

### API Endpoints

Пакет автоматически регистрирует следующие роуты:

#### Folders

- `GET /api/v1/folders` - Список папок
- `POST /api/v1/folders` - Создать папку
- `GET /api/v1/folders/{id}` - Показать папку
- `PUT /api/v1/folders/{id}` - Обновить папку
- `DELETE /api/v1/folders/{id}` - Удалить папку (переместить в корзину)
- `POST /api/v1/folders/{id}/restore` - Восстановить папку из корзины
- `GET /api/v1/folders/tree/all` - Получить дерево всех папок
- `POST /api/v1/folders/update-positions` - Обновить позиции папок

#### Media

- `GET /api/v1/media` - Список файлов
- `POST /api/v1/media` - Загрузить файл
- `GET /api/v1/media/{id}` - Показать файл
- `PUT /api/v1/media/{id}` - Обновить файл (переместить в другую папку или заменить файл)
- `DELETE /api/v1/media/{id}` - Удалить файл (переместить в корзину)
- `POST /api/v1/media/{id}/restore` - Восстановить файл из корзины
- `DELETE /api/v1/media/trash/empty` - Очистить корзину

### Примеры использования API

#### Загрузка файла

```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('folder_id', folderId); // опционально

fetch('/api/v1/media', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + token
    },
    body: formData
})
.then(response => response.json())
.then(data => console.log(data));
```

#### Получение списка файлов с фильтрацией

```javascript
// Получить файлы из папки
fetch('/api/v1/media?folder_id=1&per_page=20&page=1&sort_by=created_at&sort_order=desc')
    .then(response => response.json())
    .then(data => console.log(data));

// Поиск файлов
fetch('/api/v1/media?search=photo&type=photo')
    .then(response => response.json())
    .then(data => console.log(data));
```

#### Создание папки

```javascript
fetch('/api/v1/folders', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + token
    },
    body: JSON.stringify({
        name: 'Новая папка',
        parent_id: null // null для корневой папки
    })
})
.then(response => response.json())
.then(data => console.log(data));
```

### Использование моделей в коде

```php
use LetoceilingCoder\Media\Models\Media;
use LetoceilingCoder\Media\Models\Folder;

// Получить все файлы текущего пользователя
$media = Media::all();

// Получить файлы из конкретной папки
$folder = Folder::find(1);
$files = $folder->files;

// Создать папку
$folder = Folder::create([
    'name' => 'Мои файлы',
    'parent_id' => null
]);

// Загрузить файл (обычно через контроллер)
$media = Media::create([
    'name' => 'filename.jpg',
    'original_name' => 'original.jpg',
    'extension' => 'jpg',
    'type' => 'photo',
    'size' => 1024000,
    'folder_id' => $folder->id,
    // ...
]);
```

### Использование Vue компонента

После публикации компонента, импортируйте его в ваше приложение:

```vue
<template>
    <Media 
        v-if="showMediaManager"
        :selection-mode="false"
        :count-file="1"
        @file-selected="handleFileSelected"
    />
</template>

<script setup>
import Media from '@/vendor/media/Media.vue'

const showMediaManager = ref(true)

const handleFileSelected = (file) => {
    console.log('Выбран файл:', file)
}
</script>
```

## Структура базы данных

### Таблица `folders`

- `id` - ID папки
- `name` - Название папки
- `slug` - URL-слаг
- `src` - Иконка папки
- `parent_id` - ID родительской папки
- `position` - Позиция для сортировки
- `protected` - Защищена ли папка от удаления
- `is_trash` - Является ли папка корзиной
- `user_id` - ID пользователя (для мультипользовательского режима)
- `created_at`, `updated_at`, `deleted_at`

### Таблица `media`

- `id` - ID файла
- `name` - Имя файла на сервере
- `original_name` - Оригинальное имя файла
- `extension` - Расширение файла
- `disk` - Диск для хранения
- `width` - Ширина изображения (для фото)
- `height` - Высота изображения (для фото)
- `type` - Тип файла (photo, video, document)
- `size` - Размер файла в байтах
- `folder_id` - ID папки
- `original_folder_id` - ID оригинальной папки (для восстановления из корзины)
- `user_id` - ID пользователя
- `telegram_file_id` - ID файла в Telegram (если был загружен оттуда)
- `metadata` - Дополнительные метаданные (JSON)
- `temporary` - Временный ли файл
- `created_at`, `updated_at`, `deleted_at`

## Авторизация

По умолчанию API не защищено middleware авторизации. Для защиты добавьте middleware в контроллеры или через `MediaServiceProvider`:

```php
// В MediaServiceProvider::boot()
Route::prefix('api/v1')
    ->middleware(['api', 'auth:sanctum']) // Добавьте middleware
    ->group(function () {
        // роуты...
    });
```

## Фильтрация по пользователям

Пакет поддерживает автоматическую фильтрацию по `user_id` через scope. Это можно включить/выключить через конфигурацию:

```php
'user_scoping' => true // или false для отключения
```

При включенной фильтрации:
- Пользователи видят только свои файлы и папки
- Системные папки (с `user_id = NULL`) доступны всем
- Корзина общая для всех пользователей

Для обхода scope используйте:

```php
// Получить все файлы без фильтрации по user_id
$allMedia = Media::withoutUserScope()->get();

// Получить файлы конкретного пользователя
$userMedia = Media::forUser($userId)->get();

// Получить файлы всех пользователей
$allUsersMedia = Media::allUsers()->get();
```

## Расширение

### Добавление кастомных фильтров

Создайте свой фильтр:

```php
namespace App\Http\Filters;

use LetoceilingCoder\Media\Http\Filters\AbstractFilter;
use Illuminate\Database\Eloquent\Builder;

class MediaFilter extends AbstractFilter
{
    public const TYPE = 'type';
    
    protected function getCallbacks(): array
    {
        return [
            self::TYPE => [$this, 'type'],
        ];
    }
    
    public function type(Builder $builder, $value)
    {
        $builder->where('type', $value);
    }
}
```

### Кастомизация моделей

Если нужно расширить функциональность моделей, создайте свои модели, наследуя от моделей пакета:

```php
namespace App\Models;

use LetoceilingCoder\Media\Models\Media as BaseMedia;

class Media extends BaseMedia
{
    // Ваша кастомизация
}
```

## Поддержка

Для вопросов и предложений создайте issue в репозитории: https://github.com/letoceiling-coder/media

## Лицензия

MIT License
