<?php
// START TIME MEASUREMENT
if (!isset($_SERVER['REQUEST_TIME_FLOAT'])) {
    $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
}

// Define ROOT path — project base
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

// Include classes (without "use", because we use global alias)
require_once ROOT_PATH . 'includes/Database.php';
require_once ROOT_PATH . 'includes/Auth.php';
require_once ROOT_PATH . 'includes/StyleManager.php';
require_once ROOT_PATH . 'includes/Language.php';

// Check if system is installed
$configPath = ROOT_PATH . 'includes/config.php';
if (!file_exists($configPath)) {
    header('Location: /install/index.php');
    exit;
}

$config = require $configPath;
if (!($config['site']['installed'] ?? false)) {
    header('Location: /install/index.php');
    exit;
}

// Initialize everything
try {
    // Now getInstance() returns PDO directly
    $pdo = Database::getInstance();

    if ($pdo === null) {
        throw new RuntimeException('Database connection failed.');
    }

    $auth = new Auth($pdo);
    $styleManager = new StyleManager();
    $lang = new Language($_SESSION['lang'] ?? 'en');

} catch (Exception $e) {
    header('Location: /install/index.php');
    exit;
}

// === ADDED FUNCTION: Get site name from settings ===
function getSiteName($pdo) {
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE name = 'site_name'");
    $stmt->execute();
    return $stmt->fetchColumn() ?: 'My Tracker';
}
$siteName = getSiteName($pdo);
// =============================================================

// Language change handling
if (isset($_GET['set_lang']) && isset($_GET['lang'])) {
    $newLang = $_GET['lang'];
    if (in_array($newLang, $lang->getAvailable())) {
        $_SESSION['lang'] = $newLang;
        setcookie('lang', $newLang, time() + 365*24*3600, '/');
        // Redirect to same page but without set_lang
        $query = $_GET;
        unset($query['set_lang']);
        unset($query['lang']);
        $queryString = !empty($query) ? '?' . http_build_query($query) : '';
        $currentUrl = strtok($_SERVER['REQUEST_URI'], '?') . $queryString;
        header("Location: $currentUrl");
        exit;
    }
}

// Automatically create folders if they don't exist — relative to ROOT
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

// Create `peers` table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `peers` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `torrent_id` INT NOT NULL,
        `peer_id` VARCHAR(40) NOT NULL,
        `ip` VARCHAR(45) NOT NULL,
        `port` INT NOT NULL,
        `seeder` TINYINT(1) NOT NULL DEFAULT 0,
        `uploaded` BIGINT UNSIGNED NOT NULL DEFAULT 0,
        `downloaded` BIGINT UNSIGNED NOT NULL DEFAULT 0,
        `left` BIGINT UNSIGNED NOT NULL DEFAULT 0,
        `last_announce` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_peer` (`torrent_id`, `peer_id`),
        KEY `torrent_id` (`torrent_id`),
        KEY `seeder` (`seeder`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Exception $e) {
    // Table already exists or no need to create
}

// Function to generate URL with preserved parameters
function buildLangUrl($langCode) {
    $params = $_GET;
    $params['lang'] = $langCode;
    $params['set_lang'] = '1';
    return '?' . http_build_query($params);
}

// === STATISTICS WITH COLORED SEEDERS AND LEECHERS ===
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$totalUsers = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM torrents");
$totalTorrents = $stmt->fetchColumn();

// Check if "peers" table has "seeder" column
$hasSeederColumn = false;
try {
    $stmt = $pdo->query("SELECT seeder FROM peers LIMIT 1");
    $hasSeederColumn = true;
} catch (PDOException $e) {
    // Column "seeder" doesn't exist
}

if ($hasSeederColumn) {
    // Remove old peers
    $pdo->exec("DELETE FROM peers WHERE last_announce < NOW() - INTERVAL 30 MINUTE");

    $stmt = $pdo->query("SELECT COUNT(*) FROM peers WHERE seeder = 1");
    $seeders = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM peers WHERE seeder = 0");
    $leechers = $stmt->fetchColumn();

    $totalPeers = $seeders + $leechers;
    $seederPercentage = $totalPeers > 0 ? round(($seeders / $totalPeers) * 100, 1) : 0;

    $statsText = sprintf(
        "%s %s, %s %s (<span class=\"text-success\">%s</span> %s, <span class=\"text-primary\">%s</span> %s, %s%%)",
        number_format($totalUsers),
        $lang->get('users'),
        number_format($totalTorrents),
        $lang->get('torrents'),
        number_format($seeders),
        $lang->get('seeders'),
        number_format($leechers),
        $lang->get('leechers'),
        $seederPercentage
    );
} else {
    // Show only basic statistics
    $statsText = sprintf(
        "%s %s, %s %s",
        number_format($totalUsers),
        $lang->get('users'),
        number_format($totalTorrents),
        $lang->get('torrents')
    );
}

$greeting = '';
if ($auth->isLoggedIn()) {
    $user = $auth->getUser();
    $greeting = $lang->get('welcome') . ', <strong>' . htmlspecialchars($user['username']) . '</strong>';
}
?>
<!DOCTYPE html>
<html lang="<?= $lang->getCurrent() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="<?= $styleManager->getCSS() ?>" rel="stylesheet">
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
                <i class="bi bi-collection-fill me-1"></i><?= htmlspecialchars($siteName) ?>
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

                <!-- SEARCH — now on the right of the main menu -->
                <form class="d-flex search-form" action="/search.php" method="GET">
                    <input class="form-control me-2" type="search" name="q" placeholder="<?= $lang->get('search_placeholder') ?>" aria-label="Search">
                    <button class="btn btn-outline-secondary" type="submit"><?= $lang->get('search_button') ?></button>
                </form>
            </div>
        </div>
    </nav>

    <!-- STATISTICS BLOCK UNDER MENU -->
    <div class="bg-dark text-light py-2 px-3 d-flex justify-content-between align-items-center small">
        <div>
            <?= $statsText ?>
        </div>
        <div class="d-flex align-items-center gap-3">
            <!-- Welcome + Username -->
            <?php if ($auth->isLoggedIn()): ?>
                <?= $greeting ?>
            <?php else: ?>
                <a href="/login.php" class="text-light text-decoration-none"><?= $lang->get('login') ?></a> | 
                <a href="/register.php" class="text-light text-decoration-none"><?= $lang->get('register') ?></a>
            <?php endif; ?>

            <!-- User menu (only if logged in) -->
            <?php if ($auth->isLoggedIn()): ?>
                <li class="nav-item dropdown list-unstyled mb-0">
                    <a class="nav-link dropdown-toggle p-0 text-light" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
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
            <?php endif; ?>

            <!-- Language change -->
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

            <!-- Theme change -->
            <li class="nav-item dropdown list-unstyled mb-0">
                <a class="nav-link dropdown-toggle p-0 text-light" href="#" id="styleDropdown" role="button" data-bs-toggle="dropdown">
                    <i class="bi bi-palette"></i>
                </a>
                <ul class="dropdown-menu">
                    <?php foreach ($styleManager->getAvailable() as $style): ?>
                        <li><a class="dropdown-item" href="?style=<?= $style ?>"><i class="bi bi-circle-fill me-2" style="color: <?= $style === 'dark' ? '#333' : '#0d6efd' ?>;"></i><?= $lang->get($style) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </li>
        </div>
    </div>

    <div class="container py-4">