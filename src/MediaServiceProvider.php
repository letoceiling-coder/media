<?php

namespace LetoceilingCoder\Media;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use LetoceilingCoder\Media\Console\InstallCommand;

class MediaServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/media.php',
            'media'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {

        // Публикация конфигурации
        $this->publishes([
            __DIR__ . '/../config/media.php' => config_path('media.php'),
        ], 'media-config');

        // Публикация миграций
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'media-migrations');

        // Публикация Vue компонентов
        $this->publishes([
            __DIR__ . '/../resources/js/components/Media.vue' => resource_path('js/vendor/media/components/Media.vue'),
            __DIR__ . '/../resources/js/components/EditImage.vue' => resource_path('js/vendor/media/components/EditImage.vue'),
            __DIR__ . '/../resources/js/utils/api.js' => resource_path('js/vendor/media/utils/api.js'),
            __DIR__ . '/../resources/js/composables/useAuthToken.js' => resource_path('js/vendor/media/composables/useAuthToken.js'),
        ], 'media-components');

        // Публикация изображений (иконки папок)
        if (file_exists(__DIR__ . '/../resources/public/img/system')) {
            $this->publishes([
                __DIR__ . '/../resources/public/img/system' => public_path('img/system/media'),
            ], 'media-assets');
        }

        // Публикация CSS стилей для компонента
        $this->publishes([
            __DIR__ . '/../resources/css/media.css' => resource_path('css/vendor/media.css'),
        ], 'media-styles');

        // Загрузка миграций
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Автоматическое создание директории для загрузки
        if ($this->shouldCreateUploadDirectory()) {
            $this->createUploadDirectoryIfNotExists();
        }

        // Регистрация роутов
        $this->mapApiRoutes();

        // Регистрация команд
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }

    /**
     * Регистрация API роутов
     */
    protected function mapApiRoutes(): void
    {
        $middleware = $this->getMiddleware();
        
        Route::prefix('api/v1')
            ->middleware(array_filter(array_merge(['api'], $middleware)))
            ->group(function () {
                // Folders
                Route::get('folders/tree/all', [
                    \LetoceilingCoder\Media\Http\Controllers\Api\v1\FolderController::class,
                    'tree'
                ])->name('media.folders.tree');
                
                Route::post('folders/update-positions', [
                    \LetoceilingCoder\Media\Http\Controllers\Api\v1\FolderController::class,
                    'updatePositions'
                ])->name('media.folders.update-positions');
                
                Route::post('folders/{id}/restore', [
                    \LetoceilingCoder\Media\Http\Controllers\Api\v1\FolderController::class,
                    'restore'
                ])->name('media.folders.restore');
                
                Route::apiResource('folders', \LetoceilingCoder\Media\Http\Controllers\Api\v1\FolderController::class);
                
                // Media
                Route::post('media/{id}/restore', [
                    \LetoceilingCoder\Media\Http\Controllers\Api\v1\MediaController::class,
                    'restore'
                ])->name('media.media.restore');
                
                Route::delete('media/trash/empty', [
                    \LetoceilingCoder\Media\Http\Controllers\Api\v1\MediaController::class,
                    'emptyTrash'
                ])->name('media.media.trash.empty');
                
                Route::apiResource('media', \LetoceilingCoder\Media\Http\Controllers\Api\v1\MediaController::class);
            });
    }

    /**
     * Получить middleware для API роутов
     * Автоматически определяет наличие auth:sanctum или auth:api
     */
    protected function getMiddleware(): array
    {
        $configMiddleware = config('media.middleware.api');
        $additionalMiddleware = config('media.middleware.additional', []);

        // Если middleware указан в конфигурации, используем его
        if ($configMiddleware !== null) {
            if (is_string($configMiddleware)) {
                return array_merge([$configMiddleware], $additionalMiddleware);
            }
            if (is_array($configMiddleware)) {
                return array_merge($configMiddleware, $additionalMiddleware);
            }
        }

        // Автоматическое определение middleware
        $middleware = [];

        // Проверяем наличие auth:sanctum
        if (class_exists(\Laravel\Sanctum\Sanctum::class)) {
            $middleware[] = 'auth:sanctum';
        }
        // Иначе проверяем наличие auth:api
        elseif (config('auth.guards.api')) {
            $middleware[] = 'auth:api';
        }

        return array_merge($middleware, $additionalMiddleware);
    }


    /**
     * Проверить, нужно ли создавать директорию для загрузки
     */
    protected function shouldCreateUploadDirectory(): bool
    {
        return config('media.auto_setup.create_upload_directory', true);
    }

    /**
     * Создать директорию для загрузки, если её нет
     */
    protected function createUploadDirectoryIfNotExists(): void
    {
        $uploadPath = config('media.upload.path', 'upload');
        $fullPath = public_path($uploadPath);

        if (!file_exists($fullPath)) {
            if (!is_dir(public_path())) {
                // Если public_path не существует, пропускаем
                return;
            }
            mkdir($fullPath, 0755, true);
            
            // Создаем .gitkeep файл для сохранения пустой директории в git
            if (!file_exists($fullPath . '/.gitkeep')) {
                touch($fullPath . '/.gitkeep');
            }
        }
    }
}
