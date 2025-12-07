# Инструкция по установке пакета Media

## Шаг 1: Установка через Composer

```bash
composer require letoceiling-coder/media:^1.0
```

## Шаг 2: Публикация файлов

### Конфигурация

```bash
php artisan vendor:publish --provider="LetoceilingCoder\Media\MediaServiceProvider" --tag="media-config"
```

### Миграции

```bash
php artisan vendor:publish --provider="LetoceilingCoder\Media\MediaServiceProvider" --tag="media-migrations"
```

### Vue компонент (опционально)

```bash
php artisan vendor:publish --provider="LetoceilingCoder\Media\MediaServiceProvider" --tag="media-components"
```

### Изображения (иконки, системные изображения)

```bash
php artisan vendor:publish --provider="LetoceilingCoder\Media\MediaServiceProvider" --tag="media-assets"
```

## Шаг 3: Запуск миграций

```bash
php artisan migrate
```

После миграции будут созданы таблицы `folders` и `media`, а также начальные папки:
- Общая
- Видео
- Документы
- Корзина

## Шаг 4: Настройка конфигурации

Отредактируйте `config/media.php` или добавьте переменные в `.env`:

```env
MEDIA_MAX_SIZE=10240
MEDIA_ALLOW_ALL_TYPES=false
MEDIA_UPLOAD_PATH=upload
MEDIA_PER_PAGE_DEFAULT=20
MEDIA_PER_PAGE_MAX=100
MEDIA_USER_SCOPING=true
```

## Шаг 5: Создание директории для загрузки файлов

```bash
mkdir -p public/upload
chmod 755 public/upload
```

## Шаг 6: Настройка авторизации (рекомендуется)

Добавьте middleware в `MediaServiceProvider` или создайте свой сервис-провайдер:

```php
// В app/Providers/AppServiceProvider.php или отдельном провайдере
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')
    ->middleware(['api', 'auth:sanctum']) // или ваш middleware
    ->group(function () {
        // Роуты будут зарегистрированы автоматически
    });
```

## Готово!

Теперь пакет готов к использованию. API доступно по адресу `/api/v1/folders` и `/api/v1/media`.

Для использования Vue компонента на фронтенде, убедитесь что вы скопировали компонент из `resources/js/vendor/media/Media.vue` и импортировали его в ваше приложение.
