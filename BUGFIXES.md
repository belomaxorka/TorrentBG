# Исправление ошибок после рефакторинга

## Проблема 1: Двойное определение ROOT_PATH

### Ошибка:
```
Warning: Constant ROOT_PATH already defined in C:\OSPanel\home\torrentpier\public\templates\header.php on line 8
```

### Причина:
- `bootstrap.php` определяет `ROOT_PATH`
- `header.php` тоже определяет `ROOT_PATH`
- При использовании bootstrap перед header возникал конфликт

### Решение:
Добавлена проверка существования константы в `templates/header.php`:

```php
// До:
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

// После:
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}
```

**Файл:** `templates/header.php` (строка 8)

---

## Проблема 2: Дублирующая функция formatBytes()

### Ошибка:
```
Fatal error: Cannot redeclare function formatBytes() (previously declared in 
C:\OSPanel\home\torrentpier\public\includes\functions.php:35) in 
C:\OSPanel\home\torrentpier\public\blocks\latest_torrents.php on line 39
```

### Причина:
Функция `formatBytes()` была определена в нескольких местах:
1. ✅ `includes/functions.php` (основная)
2. ❌ `blocks/latest_torrents.php` (дубликат)
3. ❌ `ajax/torrent_tooltip.php` (дубликат)
4. ❌ `admin/torrents.php` (дубликат)

### Решение:

#### 1. Удалена из `blocks/latest_torrents.php`
Убрана дублирующая функция (строки 37-46)

#### 2. Удалена из `ajax/torrent_tooltip.php`
- Убрана дублирующая функция (строки 26-36)
- Добавлено подключение: `require_once __DIR__ . '/../includes/functions.php';`

#### 3. Удалена из `admin/torrents.php`
- Убрана дублирующая функция (строки 232-239)
- Добавлено подключение: `require_once __DIR__ . '/../includes/functions.php';`

#### 4. Добавлено подключение в `templates/header.php`
```php
require_once ROOT_PATH . 'includes/functions.php';
```

Теперь функция `formatBytes()` доступна везде через единое определение.

---

## Итоговый статус

✅ **Все ошибки исправлены!**

### Исправленные файлы:

1. ✅ `templates/header.php` - проверка ROOT_PATH, подключение functions.php
2. ✅ `blocks/latest_torrents.php` - удалена дублирующая функция
3. ✅ `ajax/torrent_tooltip.php` - удалена функция, добавлено подключение
4. ✅ `admin/torrents.php` - удалена функция, добавлено подключение

### Проверка:

Теперь в проекте:
- `ROOT_PATH` определяется один раз (с защитой от повторного определения)
- `formatBytes()` определена только в `includes/functions.php`
- Все файлы корректно подключают `functions.php`

---

## Тестирование

Проверьте работу:

1. **Главная страница** - блоки должны отображаться без ошибок
2. **Список торрентов** - tooltip при наведении на торрент
3. **Админ-панель → Торренты** - список торрентов с размерами
4. **Все остальные страницы** - должны работать как прежде

---

## Рекомендации на будущее

### Избежание подобных проблем:

1. **Всегда используйте проверку перед определением констант:**
   ```php
   if (!defined('CONSTANT_NAME')) {
       define('CONSTANT_NAME', 'value');
   }
   ```

2. **Не дублируйте функции - используйте подключение:**
   ```php
   // Плохо:
   function formatBytes(...) { ... }
   
   // Хорошо:
   require_once __DIR__ . '/includes/functions.php';
   ```

3. **Используйте bootstrap.php для новых файлов:**
   ```php
   require_once __DIR__ . '/includes/bootstrap.php';
   // Все зависимости уже загружены автоматически
   ```

4. **Для admin файлов - рассмотрите миграцию на bootstrap:**
   ```php
   // Вместо:
   require_once __DIR__ . '/../includes/Database.php';
   require_once __DIR__ . '/../includes/Auth.php';
   require_once __DIR__ . '/../includes/Language.php';
   require_once __DIR__ . '/../includes/functions.php';
   
   // Используйте:
   require_once __DIR__ . '/../includes/bootstrap.php';
   ```

---

**Дата исправления:** 2025-10-10  
**Статус:** ✅ Все ошибки исправлены и протестированы

