<?php
declare(strict_types=1);

// Защита от прямого доступа
if (!defined('APP_STARTED')) {
    define('APP_STARTED', true);
}

// Измерение времени выполнения
if (!isset($_SERVER['REQUEST_TIME_FLOAT'])) {
    $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
}

// Определяем корневой путь проекта
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

// Включаем автозагрузчик классов
require_once __DIR__ . '/autoloader.php';

// Включаем вспомогательные функции
require_once __DIR__ . '/functions.php';

// Запускаем сессию если еще не запущена
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Инициализация приложения
$app = Application::getInstance();

// Экспорт глобальных объектов для обратной совместимости
$pdo = $app->getDatabase();
$auth = $app->getAuth();
$lang = $app->getLanguage();
$styleManager = $app->getStyleManager();

// Обработка смены языка
if (isset($_GET['set_lang']) && isset($_GET['lang'])) {
    $newLang = $_GET['lang'];
    if (in_array($newLang, $lang->getAvailable())) {
        $_SESSION['lang'] = $newLang;
        setcookie('lang', $newLang, time() + 365*24*3600, '/');
        
        // Редирект без параметров смены языка
        $query = $_GET;
        unset($query['set_lang']);
        unset($query['lang']);
        $queryString = !empty($query) ? '?' . http_build_query($query) : '';
        $currentUrl = strtok($_SERVER['REQUEST_URI'], '?') . $queryString;
        header("Location: $currentUrl");
        exit;
    }
}

// Обработка смены темы
if (isset($_GET['style'])) {
    $newStyle = $_GET['style'];
    if (in_array($newStyle, $styleManager->getAvailable())) {
        $_SESSION['style'] = $newStyle;
        setcookie('style', $newStyle, time() + 365*24*3600, '/');
        
        if ($auth->isLoggedIn()) {
            $pdo->prepare("UPDATE users SET style = ? WHERE id = ?")
                ->execute([$newStyle, $auth->getUser()['id']]);
        }
    }
}

