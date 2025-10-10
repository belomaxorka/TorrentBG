<?php
/**
 * Новый улучшенный header.php
 * Использует bootstrap.php для инициализации
 * Содержит только презентационную логику
 */

// Защита от прямого доступа
if (!defined('APP_STARTED')) {
    require_once dirname(__DIR__) . '/includes/bootstrap.php';
}

// Переменные из bootstrap уже доступны: $app, $pdo, $auth, $lang, $styleManager

// Функция для генерирования URL с сохранением параметров
function buildLangUrl($langCode) {
    $params = $_GET;
    $params['lang'] = $langCode;
    $params['set_lang'] = '1';
    return '?' . http_build_query($params);
}

// Получаем статистику через Application
$stats = $app->getStatistics();

// Формируем текст статистики
if ($stats['total_peers'] > 0) {
    $statsText = sprintf(
        "%s %s, %s %s (<span class=\"text-success\">%s</span> %s, <span class=\"text-primary\">%s</span> %s, %s%%)",
        number_format($stats['users']),
        htmlspecialchars($lang->get('users')),
        number_format($stats['torrents']),
        htmlspecialchars($lang->get('torrents')),
        number_format($stats['seeders']),
        htmlspecialchars($lang->get('seeders')),
        number_format($stats['leechers']),
        htmlspecialchars($lang->get('leechers')),
        $stats['seeder_percentage']
    );
} else {
    $statsText = sprintf(
        "%s %s, %s %s",
        number_format($stats['users']),
        htmlspecialchars($lang->get('users')),
        number_format($stats['torrents']),
        htmlspecialchars($lang->get('torrents'))
    );
}

// Приветствие пользователя
$greeting = '';
if ($auth->isLoggedIn()) {
    $user = $auth->getUser();
    $greeting = htmlspecialchars($lang->get('welcome')) . ', <strong>' . htmlspecialchars($user['username']) . '</strong>';
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang->getCurrent()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($lang->get('site_title')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="<?= htmlspecialchars($styleManager->getCSS()) ?>" rel="stylesheet">
    <style>
        .search-form {
            max-width: 300px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="bi bi-collection-fill me-1"></i><?= htmlspecialchars($lang->get('site_title')) ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/torrents.php">
                            <i class="bi bi-cloud-arrow-down-fill me-1"></i><?= htmlspecialchars($lang->get('torrents')) ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/forum.php">
                            <i class="bi bi-chat-fill me-1"></i><?= htmlspecialchars($lang->get('forum')) ?>
                        </a>
                    </li>
                    <?php if ($auth->isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/upload.php">
                                <i class="bi bi-upload me-1"></i><?= htmlspecialchars($lang->get('upload_torrent')) ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if ($auth->getRank() >= 6): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/index.php">
                                <i class="bi bi-gear-fill me-1"></i><?= htmlspecialchars($lang->get('admin_panel')) ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>

                <!-- Форма поиска -->
                <form class="d-flex search-form" action="/search.php" method="GET">
                    <input class="form-control me-2" type="search" name="q" placeholder="<?= htmlspecialchars($lang->get('search_placeholder')) ?>" aria-label="Search">
                    <button class="btn btn-outline-secondary" type="submit"><?= htmlspecialchars($lang->get('search_button')) ?></button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Блок со статистикой под меню -->
    <div class="bg-dark text-light py-2 px-3 d-flex justify-content-between align-items-center small">
        <div>
            <?= $statsText ?>
        </div>
        <div class="d-flex align-items-center gap-3">
            <!-- Приветствие или логин/регистрация -->
            <?php if ($auth->isLoggedIn()): ?>
                <?= $greeting ?>
            <?php else: ?>
                <a href="/login.php" class="text-light text-decoration-none"><?= htmlspecialchars($lang->get('login')) ?></a> | 
                <a href="/register.php" class="text-light text-decoration-none"><?= htmlspecialchars($lang->get('register')) ?></a>
            <?php endif; ?>

            <!-- Пользовательское меню -->
            <?php if ($auth->isLoggedIn()): ?>
                <li class="nav-item dropdown list-unstyled mb-0">
                    <a class="nav-link dropdown-toggle p-0 text-light" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="/profile.php">
                                <i class="bi bi-person me-2"></i><?= htmlspecialchars($lang->get('profile')) ?>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="/logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i><?= htmlspecialchars($lang->get('logout')) ?>
                            </a>
                        </li>
                    </ul>
                </li>
            <?php endif; ?>

            <!-- Смена языка -->
            <li class="nav-item dropdown list-unstyled mb-0">
                <a class="nav-link dropdown-toggle p-0 text-light" href="#" id="langDropdown" role="button" data-bs-toggle="dropdown">
                    <i class="bi bi-globe"></i>
                </a>
                <ul class="dropdown-menu">
                    <?php
                    $flags = [
                        'bg' => '🇧🇬',
                        'en' => '🇬🇧',
                        'de' => '🇩🇪',
                        'fr' => '🇫🇷',
                        'ru' => '🇷🇺',
                    ];
                    foreach ($lang->getAvailable() as $code): ?>
                        <li>
                            <a class="dropdown-item" href="<?= htmlspecialchars(buildLangUrl($code)) ?>">
                                <span class="me-2"><?= $flags[$code] ?? '🌐' ?></span><?= strtoupper($code) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </li>

            <!-- Смена темы -->
            <li class="nav-item dropdown list-unstyled mb-0">
                <a class="nav-link dropdown-toggle p-0 text-light" href="#" id="styleDropdown" role="button" data-bs-toggle="dropdown">
                    <i class="bi bi-palette"></i>
                </a>
                <ul class="dropdown-menu">
                    <?php foreach ($styleManager->getAvailable() as $style): ?>
                        <li><a class="dropdown-item" href="?style=<?= htmlspecialchars($style) ?>"><i class="bi bi-circle-fill me-2" style="color: <?= $style === 'dark' ? '#333' : '#0d6efd' ?>;"></i><?= htmlspecialchars($lang->get($style)) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </li>
        </div>
    </div>

    <div class="container py-4">

