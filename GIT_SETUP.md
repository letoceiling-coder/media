# Инструкция по загрузке пакета в GitHub

## 1. Инициализация Git репозитория

```bash
cd packages/letoceiling-coder/media
git init
```

## 2. Добавление remote репозитория

```bash
git remote add origin https://github.com/letoceiling-coder/media.git
```

## 3. Проверка namespace

Namespace пакета: `LetoceilingCoder\Media`

Это соответствует стандарту PSR-4:
- Vendor name: `letoceiling-coder` → Namespace: `LetoceilingCoder`
- Package name: `media` → Namespace: `Media`
- Полный namespace: `LetoceilingCoder\Media`

Все файлы используют правильный namespace.

## 4. Первый коммит

```bash
git add .
git commit -m "Initial commit: Laravel Media Package"
```

## 5. Загрузка в GitHub

```bash
git branch -M main
git push -u origin main
```

## Структура namespace

Все классы используют namespace `LetoceilingCoder\Media`:

- `LetoceilingCoder\Media\MediaServiceProvider`
- `LetoceilingCoder\Media\Models\Media`
- `LetoceilingCoder\Media\Models\Folder`
- `LetoceilingCoder\Media\Http\Controllers\Api\v1\MediaController`
- `LetoceilingCoder\Media\Http\Controllers\Api\v1\FolderController`
- и т.д.

## Использование после публикации

После загрузки в GitHub, пакет можно установить:

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

Или напрямую:

```bash
composer require letoceiling-coder/media dev-main
```
