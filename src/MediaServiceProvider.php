<?php

namespace LetoceilingCoder\Media;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

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

        // Публикация Vue компонентов (если нужно)
        if (file_exists(__DIR__ . '/../resources/js')) {
            $this->publishes([
                __DIR__ . '/../resources/js' => resource_path('js/vendor/media'),
            ], 'media-components');
        }

        // Публикация изображений
        if (file_exists(__DIR__ . '/../resources/public/img/system')) {
            $this->publishes([
                __DIR__ . '/../resources/public/img/system' => public_path('img/system'),
            ], 'media-assets');
        }

        // Загрузка миграций
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Регистрация роутов
        $this->mapApiRoutes();
    }

    /**
     * Регистрация API роутов
     */
    protected function mapApiRoutes(): void
    {
        Route::prefix('api/v1')
            ->middleware('api')
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
}
