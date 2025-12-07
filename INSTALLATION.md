# Инструкция по установке пакета Media

## Шаг 1: Клонирование репозитория

```bash
git clone https://github.com/letoceiling-coder/media.git
```

Или добавьте как зависимость в composer.json:

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

## Шаг 2: Установка через Composer

```bash
composer require letoceiling-coder/media dev-main
```

## Шаг 3: Публикация файлов

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

## Шаг 4: Запуск миграций

```bash
php artisan migrate
```

После миграции будут созданы таблицы `folders` и `media`, а также начальные папки:
- Общая
- Видео
- Документы
- Корзина

## Шаг 5: Настройка конфигурации

Отредактируйте `config/media.php` или добавьте переменные в `.env`:

```env
MEDIA_MAX_SIZE=10240
MEDIA_ALLOW_ALL_TYPES=false
MEDIA_UPLOAD_PATH=upload
MEDIA_PER_PAGE_DEFAULT=20
MEDIA_PER_PAGE_MAX=100
MEDIA_USER_SCOPING=true
```

## Шаг 6: Создание директории для загрузки файлов

```bash
mkdir -p public/upload
chmod 755 public/upload
```

## Шаг 7: Настройка авторизации (рекомендуется)

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
