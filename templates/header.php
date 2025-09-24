<?php
// ЗАПОЧВАМЕ ИЗМЕРВАНЕ НА ВРЕМЕТО
if (!isset($_SERVER['REQUEST_TIME_FLOAT'])) {
    $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
}

// Дефинираме ROOT пътя — основата на проекта
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

// Сега използваме ROOT_PATH за да сочим към правилната директория
require_once ROOT_PATH . 'includes/Database.php';
require_once ROOT_PATH . 'includes/Auth.php';
require_once ROOT_PATH . 'includes/StyleManager.php';
require_once ROOT_PATH . 'includes/Language.php';

// Инициализираме всичко
$pdo = Database::getInstance();
$auth = new Auth($pdo);
$styleManager = new StyleManager();
$lang = new Language($_SESSION['lang'] ?? 'en');
// Обработка на смяна на език
if (isset($_GET['set_lang']) && isset($_GET['lang'])) {
    $newLang = $_GET['lang'];
    if (in_array($newLang, $lang->getAvailable())) {
        $_SESSION['lang'] = $newLang;
        setcookie('lang', $newLang, time() + 365*24*3600, '/');
        // Пренасочваме без параметъра set_lang, за да не се повтаря
        $currentUrl = strtok($_SERVER['REQUEST_URI'], '?');
        header("Location: $currentUrl");
        exit;
    }
}

// Автоматично създаване на папки ако не съществуват — относително към ROOT
$requiredDirs = [
    'torrents',
    'subtitles',
    'images/posters',
    'images/categories',
    'images/forums',
    'images/smiles',
];

foreach ($requiredDirs as $dir) {
    if (!is_dir(ROOT_PATH . $dir)) {
        mkdir(ROOT_PATH . $dir, 0755, true);
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang->getCurrent() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang->get('site_title') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="<?= $styleManager->getCSS() ?>" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="bi bi-collection-fill me-1"></i><?= $lang->get('site_title') ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/torrents.php">
                            <i class="bi bi-cloud-arrow-down-fill me-1"></i><?= $lang->get('torrents') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/forum.php">
                            <i class="bi bi-chat-fill me-1"></i><?= $lang->get('forum') ?>
                        </a>
                    </li>
                    <?php if ($auth->isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/upload.php">
                                <i class="bi bi-upload me-1"></i><?= $lang->get('upload_torrent') ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if ($auth->getRank() >= 6): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/index.php">
                                <i class="bi bi-gear-fill me-1"></i><?= $lang->get('admin_panel') ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>

                <!-- Потребителски меню -->
                <ul class="navbar-nav">
                    <?php if ($auth->isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($auth->getUser()['username']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="/profile.php">
                                        <i class="bi bi-person me-2"></i><?= $lang->get('profile') ?>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="/logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i><?= $lang->get('logout') ?>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login.php">
                                <i class="bi bi-box-arrow-in-right me-1"></i><?= $lang->get('login') ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/register.php">
                                <i class="bi bi-person-plus-fill me-1"></i><?= $lang->get('register') ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Смяна на език -->
                    <!-- Смяна на език -->
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="langDropdown" role="button" data-bs-toggle="dropdown">
        <i class="bi bi-globe me-1"></i><?= strtoupper($lang->getCurrent()) ?>
    </a>
    <ul class="dropdown-menu">
        <?php foreach ($lang->getAvailable() as $code): ?>
            <li>
                <a class="dropdown-item" href="?lang=<?= $code ?>&set_lang=1">
                    <i class="bi bi-translate me-2"></i><?= strtoupper($code) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</li>

                    <!-- Смяна на тема -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="styleDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-palette me-1"></i><?= $lang->get($styleManager->getCurrent()) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <?php foreach ($styleManager->getAvailable() as $style): ?>
                                <li><a class="dropdown-item" href="?style=<?= $style ?>"><i class="bi bi-circle-fill me-2" style="color: <?= $style === 'dark' ? '#333' : '#0d6efd' ?>;"></i><?= $lang->get($style) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">