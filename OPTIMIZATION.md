# Оптимизация пакета для легкой установки

## Реализованные улучшения

### ✅ 1. Автоматическое определение middleware

**Проблема:** Нужно было вручную редактировать vendor файл для добавления middleware.

**Решение:** Пакет автоматически определяет middleware:
- Проверяет наличие Laravel Sanctum → использует `auth:sanctum`
- Иначе проверяет настройку `auth.guards.api` → использует `auth:api`
- Можно переопределить через конфигурацию

**Использование:**

```php
// В config/media.php или через .env
'middleware' => [
    'api' => 'auth:sanctum', // или null для отключения
    'additional' => ['throttle:60,1'], // дополнительные middleware
],
```

Или через переменные окружения:

```env
MEDIA_MIDDLEWARE_API=auth:sanctum
MEDIA_MIDDLEWARE_ADDITIONAL=throttle:60,1
```

### ✅ 2. Автоматическое создание директорий

**Проблема:** Нужно было вручную создавать директорию `public/upload`.

**Решение:** 
- Директория создается автоматически при первой загрузке файла
- Создается в `boot()` методе ServiceProvider (если включено в конфиге)
- Создается `.gitkeep` файл для сохранения структуры

**Настройка:**

```env
MEDIA_AUTO_CREATE_UPLOAD_DIR=true  # по умолчанию включено
```

### ✅ 3. Конфигурация через переменные окружения

Все настройки можно изменить через `.env` без публикации конфигурации:

```env
# Middleware
MEDIA_MIDDLEWARE_API=auth:sanctum
MEDIA_MIDDLEWARE_ADDITIONAL=throttle:60,1

# Загрузка
MEDIA_MAX_SIZE=10240
MEDIA_UPLOAD_PATH=upload

# Пагинация
MEDIA_PER_PAGE_DEFAULT=20
MEDIA_PER_PAGE_MAX=100

# Фильтрация
MEDIA_USER_SCOPING=true

# Автоматические настройки
MEDIA_AUTO_CREATE_UPLOAD_DIR=true
```

### ✅ 4. Минимальная установка

Теперь для установки достаточно:

```bash
composer require letoceiling-coder/media
php artisan migrate
```

Пакет работает автоматически с оптимальными настройками по умолчанию.

## Структура конфигурации

### Основные разделы

1. **upload** - настройки загрузки файлов
2. **pagination** - настройки пагинации
3. **user_scoping** - фильтрация по пользователям
4. **trash** - настройки корзины
5. **middleware** - настройки middleware (новое)
6. **auto_setup** - автоматические настройки (новое)

### Middleware настройки

```php
'middleware' => [
    // Основной middleware (null = автопределение, 'auth:sanctum' = явно указать)
    'api' => env('MEDIA_MIDDLEWARE_API', null),
    
    // Дополнительные middleware
    'additional' => env('MEDIA_MIDDLEWARE_ADDITIONAL', '') 
        ? explode(',', env('MEDIA_MIDDLEWARE_ADDITIONAL', '')) 
        : [],
],
```

## Логика автоматического определения middleware

```php
1. Проверяет config('media.middleware.api')
   ├─ Если указан → использует его
   └─ Если null → переходит к автопределению

2. Автоматическое определение:
   ├─ Проверяет наличие класса \Laravel\Sanctum\Sanctum
   │  └─ Если есть → использует 'auth:sanctum'
   └─ Иначе проверяет config('auth.guards.api')
      └─ Если настроен → использует 'auth:api'
      └─ Если нет → роуты без middleware (для разработки)
```

## Рекомендации по использованию

### Для продакшена

1. Опубликуйте конфигурацию:
   ```bash
   php artisan vendor:publish --tag=media-config
   ```

2. Настройте middleware явно в `config/media.php`:
   ```php
   'middleware' => [
       'api' => 'auth:sanctum',
   ],
   ```

3. Настройте через `.env`:
   ```env
   MEDIA_MIDDLEWARE_API=auth:sanctum
   ```

### Для разработки

Пакет работает "из коробки" без дополнительных настроек. Middleware определится автоматически.

## Миграция с предыдущей версии

Если вы уже использовали пакет и редактировали `vendor/letoceiling-coder/media/src/MediaServiceProvider.php`:

1. Удалите изменения из vendor файла
2. Добавьте в `config/media.php` (или `.env`):
   ```php
   'middleware' => [
       'api' => 'auth:sanctum', // ваш middleware
   ],
   ```
3. Очистите кэш конфигурации:
   ```bash
   php artisan config:clear
   ```

Теперь ваши настройки не потеряются при обновлении пакета!
