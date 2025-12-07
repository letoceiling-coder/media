<?php

namespace LetoceilingCoder\Media\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:install 
                            {--force : Перезаписать существующие файлы}
                            {--no-components : Не публиковать Vue компоненты}
                            {--no-styles : Не публиковать CSS стили}
                            {--no-assets : Не публиковать иконки}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Установка и настройка пакета letoceiling-coder/media';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Установка пакета letoceiling-coder/media...');
        $this->newLine();

        $force = $this->option('force');

        // Публикация миграций
        $this->info('📦 Публикация миграций...');
        $this->call('vendor:publish', [
            '--provider' => 'LetoceilingCoder\\Media\\MediaServiceProvider',
            '--tag' => 'media-migrations',
            '--force' => $force,
        ]);

        // Публикация конфигурации
        $this->info('⚙️  Публикация конфигурации...');
        $this->call('vendor:publish', [
            '--provider' => 'LetoceilingCoder\\Media\\MediaServiceProvider',
            '--tag' => 'media-config',
            '--force' => $force,
        ]);

        // Публикация Vue компонентов
        if (!$this->option('no-components')) {
            $this->info('📝 Публикация Vue компонентов...');
            $this->call('vendor:publish', [
                '--provider' => 'LetoceilingCoder\\Media\\MediaServiceProvider',
                '--tag' => 'media-components',
                '--force' => $force,
            ]);
        }

        // Публикация CSS стилей
        if (!$this->option('no-styles')) {
            $this->info('🎨 Публикация CSS стилей...');
            $this->call('vendor:publish', [
                '--provider' => 'LetoceilingCoder\\Media\\MediaServiceProvider',
                '--tag' => 'media-styles',
                '--force' => $force,
            ]);
        }

        // Публикация иконок
        if (!$this->option('no-assets')) {
            $this->info('🖼️  Публикация системных иконок...');
            $this->call('vendor:publish', [
                '--provider' => 'LetoceilingCoder\\Media\\MediaServiceProvider',
                '--tag' => 'media-assets',
                '--force' => $force,
            ]);
        }

        // Проверка подключения CSS
        $this->checkCssImport();

        // Проверка миграций
        $this->checkMigrations();

        // Вывод информации о следующих шагах
        $this->newLine();
        $this->info('✅ Установка завершена!');
        $this->newLine();
        $this->displayNextSteps();

        return Command::SUCCESS;
    }

    /**
     * Проверить подключение CSS в app.css
     */
    protected function checkCssImport(): void
    {
        $appCssPath = resource_path('css/app.css');
        
        if (!File::exists($appCssPath)) {
            $this->warn('⚠️  Файл resources/css/app.css не найден');
            return;
        }

        $content = File::get($appCssPath);
        
        if (!str_contains($content, 'vendor/media.css')) {
            $this->warn('⚠️  CSS стили не подключены в app.css');
            $this->line('   Добавьте в resources/css/app.css:');
            $this->line('   @import \'./vendor/media.css\';');
            $this->newLine();
        } else {
            $this->info('✅ CSS стили подключены');
        }
    }

    /**
     * Проверить наличие миграций
     */
    protected function checkMigrations(): void
    {
        $this->info('📊 Проверка миграций...');
        $this->line('   Выполните: php artisan migrate');
        $this->newLine();
    }

    /**
     * Отобразить следующие шаги
     */
    protected function displayNextSteps(): void
    {
        $this->comment('📋 Следующие шаги:');
        $this->newLine();
        
        $this->line('1. Выполните миграции:');
        $this->line('   <fg=cyan>php artisan migrate</>');
        $this->newLine();

        $this->line('2. Установите JavaScript зависимости:');
        $this->line('   <fg=cyan>npm install vue@^3.5.0 vue-router@^4.6.0 fslightbox-vue@^3.0.1 sweetalert2@^11.26.3 vue-advanced-cropper@^2.8.9</>');
        $this->newLine();

        if (!$this->option('no-styles')) {
            $appCssPath = resource_path('css/app.css');
            if (File::exists($appCssPath)) {
                $content = File::get($appCssPath);
                if (!str_contains($content, 'vendor/media.css')) {
                    $this->line('3. Подключите CSS стили в resources/css/app.css:');
                    $this->line('   <fg=cyan>@import \'./vendor/media.css\';</>');
                    $this->newLine();
                }
            }
        }

        $this->line('4. ⚠️  ВАЖНО: Добавьте роут для редактирования изображений в ваш Vue Router.');
        $this->line('   Откройте файл с роутами админки (например, resources/js/router/admin.js)');
        $this->line('   и добавьте следующий роут внутри children роута /admin:');
        $this->newLine();
        $this->line('   <fg=cyan>{');
        $this->line('       path: \'media/:id/edit\',');
        $this->line('       name: \'admin.media.edit\',');
        $this->line('       component: () => import(\'@/vendor/media/components/EditImage.vue\'),');
        $this->line('       meta: { title: \'Редактировать изображение\' },');
        $this->line('   },</>');
        $this->newLine();
        $this->warn('   Без этого роута функция редактирования фото не будет работать!');

        $this->newLine();
        $this->line('5. Пересоберите фронтенд:');
        $this->line('   <fg=cyan>npm run build</>');
        $this->newLine();
    }
}
