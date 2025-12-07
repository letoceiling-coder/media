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
                            {--no-assets : Не публиковать иконки}
                            {--auto-fix-routes : Автоматически исправить порядок роутов}';

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

        // Проверка и исправление роута
        if (!$this->option('no-components')) {
            $this->checkAndFixRoute();
        }

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
     * Проверить и исправить роут для редактирования
     */
    protected function checkAndFixRoute(): void
    {
        $this->info('🔍 Проверка роутов...');
        
        // Ищем файлы роутов
        $routerFiles = [
            resource_path('js/router/admin.js'),
            resource_path('js/router/index.js'),
            resource_path('js/router.js'),
            resource_path('js/routes/admin.js'),
            resource_path('js/routes.js'),
            resource_path('js/app.js'),
        ];

        $routeFound = false;
        $routeFile = null;
        $needsFix = false;

        foreach ($routerFiles as $file) {
            if (File::exists($file)) {
                $content = File::get($file);
                
                // Проверяем наличие роута
                $hasEditRoute = str_contains($content, 'admin.media.edit') || 
                    (str_contains($content, 'media/:id/edit') && str_contains($content, 'EditImage.vue'));
                
                // Проверяем порядок роутов - edit должен быть ПЕРЕД media
                $hasMediaRoute = str_contains($content, "path: 'media'") || str_contains($content, 'path: "media"');
                $hasEditRoutePattern = str_contains($content, "path: 'media/:id/edit'") || str_contains($content, 'path: "media/:id/edit"');
                
                if ($hasEditRoute && $hasMediaRoute && $hasEditRoutePattern) {
                    // Найдем позиции роутов
                    $mediaPos = strpos($content, "path: 'media'");
                    if ($mediaPos === false) {
                        $mediaPos = strpos($content, 'path: "media"');
                    }
                    
                    $editPos = strpos($content, "path: 'media/:id/edit'");
                    if ($editPos === false) {
                        $editPos = strpos($content, 'path: "media/:id/edit"');
                    }
                    
                    // Если роут media идет ПЕРЕД edit, порядок неправильный
                    if ($mediaPos !== false && $editPos !== false && $mediaPos < $editPos) {
                        $needsFix = true;
                        $routeFile = $file;
                        $routeFound = true;
                        break;
                    } elseif ($hasEditRoute) {
                        $routeFound = true;
                        $routeFile = $file;
                        break;
                    }
                } elseif ($hasEditRoute) {
                    $routeFound = true;
                    $routeFile = $file;
                }
            }
        }

        if ($needsFix) {
            $this->newLine();
            $this->error('⚠️  Найдена проблема с порядком роутов!');
            $this->warn('   Роут "media/:id/edit" должен быть ПЕРЕД роутом "media"');
            
            if ($this->option('auto-fix-routes') || $this->confirm('   Автоматически исправить порядок роутов?', true)) {
                $this->fixRouteOrder($routeFile);
                $this->info('✅ Порядок роутов исправлен!');
            } else {
                $this->warn('   Ручное исправление: переместите роут "media/:id/edit" ПЕРЕД роутом "media"');
            }
            $this->newLine();
        } elseif ($routeFound) {
            $this->info('✅ Роут для редактирования изображений найден и правильно расположен');
        } else {
            $this->newLine();
            $this->error('❌ Роут для редактирования изображений НЕ найден!');
            $this->warn('   Функция редактирования фото не будет работать без этого роута!');
            $this->newLine();
        }
    }

    /**
     * Исправить порядок роутов в файле
     */
    protected function fixRouteOrder(string $filePath): void
    {
        $content = File::get($filePath);
        
        // Используем более простой подход - заменяем оба роута на правильный порядок
        // Ищем блок children в admin роуте
        $content = preg_replace_callback(
            '/(path:\s*[\'"]\/admin[\'"].*?children:\s*\[)(.*?)(\s*\])/s',
            function ($matches) {
                $childrenContent = $matches[2];
                
                // Ищем роуты media
                $mediaPattern = '/\{\s*path:\s*[\'"]media[\'"],\s*name:\s*[\'"]admin\.media[\'"],\s*component:[^}]+\},\s*/s';
                $editPattern = '/\{\s*path:\s*[\'"]media\/:id\/edit[\'"],\s*name:\s*[\'"]admin\.media\.edit[\'"],\s*component:[^}]+\},\s*/s';
                
                preg_match($mediaPattern, $childrenContent, $mediaMatch);
                preg_match($editPattern, $childrenContent, $editMatch);
                
                if ($mediaMatch && $editMatch) {
                    // Удаляем оба роута
                    $childrenContent = str_replace($mediaMatch[0], '', $childrenContent);
                    $childrenContent = str_replace($editMatch[0], '', $childrenContent);
                    
                    // Определяем отступ из первого роута
                    preg_match('/(\s*)\{/', $childrenContent, $indentMatch);
                    $indent = $indentMatch[1] ?? str_repeat(' ', 20);
                    
                    // Форматируем роуты с правильным отступом
                    $editRoute = trim($editMatch[0]);
                    $mediaRoute = trim($mediaMatch[0]);
                    
                    // Добавляем в начало массива children (перед первым роутом или в конец если пусто)
                    $firstRoutePos = strpos($childrenContent, '{');
                    if ($firstRoutePos !== false) {
                        // Вставляем перед первым роутом
                        $newRoutes = $indent . $editRoute . "\n" . $indent . $mediaRoute . "\n";
                        $childrenContent = substr_replace($childrenContent, $newRoutes, $firstRoutePos, 0);
                    } else {
                        // Если нет других роутов, просто добавляем
                        $childrenContent .= $indent . $editRoute . "\n" . $indent . $mediaRoute . "\n";
                    }
                }
                
                return $matches[1] . $childrenContent . $matches[3];
            },
            $content,
            1
        );
        
        File::put($filePath, $content);
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

        // Проверяем роут еще раз для вывода
        $routerFiles = [
            resource_path('js/router/admin.js'),
            resource_path('js/router/index.js'),
            resource_path('js/router.js'),
            resource_path('js/routes/admin.js'),
            resource_path('js/routes.js'),
            resource_path('js/app.js'),
        ];

        $routeFound = false;
        $routeFile = null;
        
        foreach ($routerFiles as $file) {
            if (File::exists($file)) {
                $content = File::get($file);
                
                if (str_contains($content, 'admin.media.edit') || 
                    (str_contains($content, 'media/:id/edit') && str_contains($content, 'EditImage.vue'))) {
                    // Проверяем порядок
                    $mediaPos = strpos($content, "path: 'media'");
                    if ($mediaPos === false) {
                        $mediaPos = strpos($content, 'path: "media"');
                    }
                    
                    $editPos = strpos($content, "path: 'media/:id/edit'");
                    if ($editPos === false) {
                        $editPos = strpos($content, 'path: "media/:id/edit"');
                    }
                    
                    if ($mediaPos !== false && $editPos !== false && $mediaPos < $editPos) {
                        // Неправильный порядок
                        $this->newLine();
                        $this->error('⚠️  ⚠️  ⚠️  ВАЖНО: Порядок роутов неправильный! ⚠️  ⚠️  ⚠️');
                        $this->newLine();
                        $this->line('   Роут "media/:id/edit" должен быть ПЕРЕД роутом "media"');
                        $this->line('   Запустите: <fg=cyan>php artisan media:install --auto-fix-routes</>');
                        $this->newLine();
                        break;
                    } else {
                        $routeFound = true;
                    }
                    break;
                }
            }
        }

        if (!$routeFound && !$this->option('no-components')) {
            $this->newLine();
            $this->error('⚠️  ⚠️  ⚠️  ВАЖНО: Добавьте роут для редактирования изображений! ⚠️  ⚠️  ⚠️');
            $this->newLine();
            $this->line('   Откройте файл с роутами админки (например, resources/js/router/admin.js)');
            $this->line('   и добавьте следующий роут ВНУТРИ children роута /admin, ПЕРЕД роутом "media":');
            $this->newLine();
            $this->line('   <fg=cyan>{');
            $this->line('       path: \'media/:id/edit\',');
            $this->line('       name: \'admin.media.edit\',');
            $this->line('       component: () => import(\'@/vendor/media/components/EditImage.vue\'),');
            $this->line('       meta: { title: \'Редактировать изображение\' },');
            $this->line('   },</>');
            $this->line('   <fg=yellow>// Роут должен быть ПЕРЕД роутом "media"!</>');
            $this->newLine();
            $this->error('   БЕЗ ЭТОГО РОУТА ФУНКЦИЯ РЕДАКТИРОВАНИЯ ФОТО НЕ БУДЕТ РАБОТАТЬ!');
            $this->newLine();
        }

        $this->line('4. Пересоберите фронтенд:');
        $this->line('   <fg=cyan>npm run build</>');
        $this->newLine();
    }
}
