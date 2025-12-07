# Пошаговая инструкция по установке и настройке

## Полная установка с Vue компонентами

### Шаг 1: Установка пакета

```bash
composer require letoceiling-coder/media
```

### Шаг 2: Запуск миграций

```bash
php artisan migrate
```

### Шаг 3: Публикация конфигурации (опционально, для кастомизации)

```bash
php artisan vendor:publish --tag=media-config
```

### Шаг 4: Публикация Vue компонентов

```bash
php artisan vendor:publish --tag=media-components
```

Это создаст:
- `resources/js/vendor/media/components/Media.vue`
- `resources/js/vendor/media/utils/api.js`
- `resources/js/vendor/media/composables/useAuthToken.js`

### Шаг 5: Публикация CSS стилей (ОБЯЗАТЕЛЬНО!)

```bash
php artisan vendor:publish --tag=media-styles
```

Это создаст `resources/css/vendor/media.css` с необходимыми CSS переменными.

### Шаг 6: Подключение CSS стилей

**Важно:** Без этого шага компонент будет отображаться неправильно!

Добавьте в `resources/css/app.css`:

```css
@import './vendor/media.css';
```

Или в `resources/js/app.js`:

```javascript
import '../css/vendor/media.css'
```

### Шаг 7: Установка зависимостей

```bash
npm install vue@^3.5.0 vue-router@^4.6.0 fslightbox-vue@^3.0.1 sweetalert2@^11.26.3 vue-advanced-cropper@^2.8.9
```

### Шаг 8: Публикация иконок (опционально)

```bash
php artisan vendor:publish --tag=media-assets
```

Это скопирует иконки папок в `public/img/system/media/`.

### Шаг 9: Пересборка фронтенда

```bash
npm run build
# или для разработки
npm run dev
```

### Шаг 10: Использование компонента

Создайте страницу или используйте компонент:

```vue
<template>
  <Media />
</template>

<script setup>
import Media from '@/vendor/media/components/Media.vue'
</script>
```

## Проверка установки

После выполнения всех шагов:

1. ✅ Пакет установлен
2. ✅ Миграции выполнены
3. ✅ Vue компоненты опубликованы
4. ✅ CSS стили опубликованы и подключены
5. ✅ Зависимости установлены
6. ✅ Иконки опубликованы (опционально)

Компонент должен отображаться с правильными стилями и всеми функциями!

## Возможные проблемы

### Проблема: Компонент отображается без стилей

**Решение:** Убедитесь, что:
1. CSS стили опубликованы (`php artisan vendor:publish --tag=media-styles`)
2. CSS файл подключен в `app.css` или `app.js`
3. Фронтенд пересобран (`npm run build`)

### Проблема: Иконки папок не отображаются

**Решение:** Опубликуйте иконки:
```bash
php artisan vendor:publish --tag=media-assets
```

Или проверьте путь в компоненте - он автоматически пробует разные варианты путей.

### Проблема: Ошибки в консоли браузера

**Решение:** Убедитесь, что все зависимости установлены:
```bash
npm install vue@^3.5.0 vue-router@^4.6.0 fslightbox-vue@^3.0.1 sweetalert2@^11.26.3 vue-advanced-cropper@^2.8.9
```
