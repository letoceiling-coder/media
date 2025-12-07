# Vue компоненты для пакета Media

Пакет включает полнофункциональный Vue компонент для работы с медиа-файлами.

## Установка зависимостей

Перед использованием Vue компонентов необходимо установить зависимости:

```bash
npm install vue@^3.5.0 vue-router@^4.6.0 fslightbox-vue@^3.0.1 sweetalert2@^11.26.3 vue-advanced-cropper@^2.8.9
```

Или через yarn:

```bash
yarn add vue@^3.5.0 vue-router@^4.6.0 fslightbox-vue@^3.0.1 sweetalert2@^11.26.3 vue-advanced-cropper@^2.8.9
```

## Публикация компонентов

Опубликуйте Vue компоненты и утилиты:

```bash
php artisan vendor:publish --tag=media-components
```

Компоненты будут опубликованы в:
- `resources/js/vendor/media/components/Media.vue` - основной компонент
- `resources/js/vendor/media/components/ImageEditor.vue` - компонент редактора изображений с функцией обрезки
- `resources/js/vendor/media/utils/api.js` - утилиты для API запросов
- `resources/js/vendor/media/composables/useAuthToken.js` - composable для авторизации

## Публикация CSS стилей

**ВАЖНО:** Для корректного отображения компонента необходимо опубликовать CSS стили:

```bash
php artisan vendor:publish --tag=media-styles
```

Затем подключите стили в вашем главном CSS файле (например, `resources/css/app.css`):

```css
@import './vendor/media.css';
```

Или в вашем `app.js`:

```javascript
import '../css/vendor/media.css'
```

## Использование компонента

### Базовое использование

```vue
<template>
  <Media />
</template>

<script setup>
import Media from '@/vendor/media/components/Media.vue'
// Импортируйте стили, если не подключили глобально
import '@/css/vendor/media.css'
</script>
```

### Режим выбора файлов

Компонент поддерживает режим выбора файлов (selection mode):

```vue
<template>
  <Media 
    :selection-mode="true"
    :count-file="1"
    @file-selected="handleFileSelected"
  />
</template>

<script setup>
import { ref } from 'vue'
import Media from '@/vendor/media/components/Media.vue'

const handleFileSelected = (file) => {
  console.log('Выбран файл:', file)
  // file содержит: id, name, url, type, size и другие данные
}
</script>
```

### Настройка API базового URL

По умолчанию компонент использует `/api/v1` как базовый URL. Если нужно изменить, отредактируйте файл `resources/js/vendor/media/utils/api.js`:

```javascript
const API_BASE = '/api/v1'; // Измените на свой URL
```

### Настройка авторизации

Компонент автоматически использует токен из `localStorage.getItem('token')`. Если нужно изменить источник токена, отредактируйте `resources/js/vendor/media/composables/useAuthToken.js`.

## Пропсы компонента

| Проп | Тип | По умолчанию | Описание |
|------|-----|--------------|----------|
| `selectionMode` | Boolean | `false` | Включить режим выбора файлов |
| `countFile` | Number | `1` | Количество файлов, которые можно выбрать |
| `selectedFiles` | Array | `[]` | Массив уже выбранных файлов |

## События

| Событие | Параметры | Описание |
|---------|-----------|----------|
| `file-selected` | `file` | Вызывается при выборе файла в режиме selectionMode |

## Функциональность

Компонент включает:

- ✅ Загрузка файлов (drag & drop и через кнопку)
- ✅ Просмотр папок и файлов
- ✅ Создание и удаление папок
- ✅ Навигация по папкам с хлебными крошками
- ✅ Поиск и фильтрация файлов
- ✅ Сортировка файлов
- ✅ Пагинация
- ✅ Превью изображений и видео (lightbox)
- ✅ Перемещение файлов между папками
- ✅ Корзина с восстановлением
- ✅ Удаление файлов и папок
- ✅ Защищенные папки
- ✅ Сохранение текущей папки в localStorage

## Стилизация

Компонент использует Tailwind CSS классы и CSS переменные. Для корректного отображения необходимо:

1. **Опубликовать CSS стили:**
   ```bash
   php artisan vendor:publish --tag=media-styles
   ```

2. **Подключить стили** в ваш проект (в `app.css` или `app.js`):
   ```css
   @import './vendor/media.css';
   ```

3. **Убедиться, что Tailwind CSS настроен** в вашем проекте.

### Кастомизация цветов

Вы можете переопределить CSS переменные в своем `app.css`:

```css
:root {
    --accent: 210 40% 96.1%; /* Ваш цвет акцента */
    --background: 0 0% 100%; /* Ваш цвет фона */
    /* ... другие переменные */
}
```

Основные классы для кастомизации:
- `bg-accent` - основной цвет акцента
- `text-accent` - цвет текста акцента
- `border-accent` - цвет границ акцента
- `bg-background` - цвет фона
- `text-foreground` - цвет текста
- `text-muted-foreground` - приглушенный цвет текста

## Интеграция с роутером

Для полноценной работы необходимо добавить роут в ваш Vue Router:

```javascript
// router/index.js
import Media from '@/vendor/media/components/Media.vue'

const routes = [
  {
    path: '/admin/media',
    name: 'admin.media',
    component: Media,
    meta: { requiresAuth: true }
  }
]
```

## Примеры использования

### Простой медиа-менеджер

```vue
<template>
  <div>
    <h1>Медиа библиотека</h1>
    <Media />
  </div>
</template>

<script setup>
import Media from '@/vendor/media/components/Media.vue'
</script>
```

### Выбор изображения для формы

```vue
<template>
  <div>
    <div v-if="selectedImage">
      <img :src="selectedImage.url" :alt="selectedImage.original_name" />
      <button @click="selectedImage = null">Очистить</button>
    </div>
    <button @click="showMediaManager = true">Выбрать изображение</button>
    
    <Media 
      v-if="showMediaManager"
      :selection-mode="true"
      :count-file="1"
      @file-selected="handleImageSelected"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Media from '@/vendor/media/components/Media.vue'

const selectedImage = ref(null)
const showMediaManager = ref(false)

const handleImageSelected = (file) => {
  if (file.type === 'photo') {
    selectedImage.value = file
    showMediaManager.value = false
  }
}
</script>
```

## Требования

- Vue 3.5+
- Vue Router 4.6+
- Tailwind CSS (для стилей)
- fslightbox-vue (для превью изображений)
- sweetalert2 (для модальных окон)

## Поддержка

При возникновении проблем создайте issue в репозитории: https://github.com/letoceiling-coder/media/issues
