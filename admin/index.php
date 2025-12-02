<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Language.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$lang = new Language($_SESSION['lang'] ?? 'en');

// Проверка за администраторски права (само Owner)
if ($auth->getRank() < 6) {
    die('<div class="container mt-5"><div class="alert alert-danger">' . $lang->get('no_permission') . '</div></div>');
}

// Статистики
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalTorrents = $pdo->query("SELECT COUNT(*) FROM torrents")->fetchColumn();
$totalForums = $pdo->query("SELECT COUNT(*) FROM forums")->fetchColumn();
$totalCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$pendingTranslations = $pdo->query("SELECT COUNT(*) FROM translations WHERE status = 'pending'")->fetchColumn();

require_once __DIR__ . '/../templates/header.php';
?>

<!-- ✅ ВГРАДЕН CSS ЗА АДМИН ПАНЕЛ -->
<style>
/* Тъмна навигация */
.navbar {
    background-color: #212529 !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.navbar-brand,
.navbar-nav .nav-link {
    color: #dee2e6 !important;
}

.navbar-nav .nav-link:hover,
.navbar-nav .nav-link.active {
    color: #ffffff !important;
    background-color: #495057;
}

/* Заглавие */
.admin-panel-title {
    background-color: #0d6efd;
    color: white;
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    border-radius: 4px;
    font-weight: bold;
    font-size: 1.3rem;
}

/* Карти */
.admin-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    margin-bottom: 1rem;
    overflow: hidden;
}

/* Цветни заглавия */
.card-header.bg-primary { background-color: #0d6efd !important; }
.card-header.bg-success { background-color: #28a745 !important; }
.card-header.bg-info { background-color: #0dcaf0 !important; }
.card-header.bg-warning { background-color: #ffc107 !important; color: #212529 !important; }
.card-header.bg-danger { background-color: #dc3545 !important; }
.card-header.bg-secondary { background-color: #6c757d !important; }
.card-header.bg-dark { background-color: #212529 !important; }

/* Пурпурен цвят */
.bg-purple {
    background-color: #6f42c1 !important;
}
.btn-purple {
    background-color: #6f42c1;
    color: white;
    border-color: #6f42c1;
}
.btn-purple:hover {
    background-color: #5a35a3;
    border-color: #543196;
}
</style>

<div class="container-fluid">
    <h2 class="mb-4 admin-panel-title">🛠️ <?= $lang->get('admin_control_panel') ?></h2>
    
    <div class="alert alert-info">
        <?= $lang->get('welcome_admin') ?>, <strong><?= htmlspecialchars($auth->getUser()['username']) ?></strong>!
    </div>

    <div class="row">
        <!-- Статистики -->
        <div class="col-md-6 mb-4">
            <div class="card admin-card h-100">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">📊 <?= $lang->get('statistics') ?></h4>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span><?= $lang->get('total_users') ?>:</span>
                            <strong><?= $totalUsers ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><?= $lang->get('total_torrents') ?>:</span>
                            <strong><?= $totalTorrents ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><?= $lang->get('total_forums') ?>:</span>
                            <strong><?= $totalForums ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><?= $lang->get('total_categories') ?>:</span>
                            <strong><?= $totalCategories ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><?= $lang->get('pending_translations') ?>:</span>
                            <strong class="text-warning"><?= $pendingTranslations ?></strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Бързи линкове -->
        <div class="col-md-6 mb-4">
            <div class="card admin-card h-100">
                <div class="card-header bg-purple text-white">
                    <h4 class="mb-0">⚡ <?= $lang->get('quick_links') ?></h4>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="/admin/users.php" class="btn btn-primary"><?= $lang->get('manage_users') ?></a>
                        <a href="/admin/ranks.php" class="btn btn-success"><?= $lang->get('manage_ranks_permissions') ?></a>
                        <a href="/admin/torrents.php" class="btn btn-info"><?= $lang->get('manage_torrents') ?></a>
                        <a href="/admin/forums.php" class="btn btn-warning"><?= $lang->get('manage_forums') ?></a>
                        <a href="/admin/categories.php" class="btn btn-secondary"><?= $lang->get('manage_categories') ?></a>
                        <a href="/admin/blocks.php" class="btn btn-danger"><?= $lang->get('manage_blocks') ?></a>
                        <a href="/admin/polls.php" class="btn btn-dark"><?= $lang->get('manage_polls') ?></a>
                        <a href="/admin/translations.php" class="btn btn-purple"><?= $lang->get('manage_translations') ?></a>
                        <a href="/admin/scrape.php" class="btn btn-primary"><?= $lang->get('tracker_statistics') ?></a>
                        <!-- ✅ Добавен линк към настройките -->
                        <a href="/admin/settings.php" class="btn btn-outline-purple"><?= $lang->get('system_settings') ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Останалите редове са същите, но с цветни заглавия -->
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card admin-card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">👥 <?= $lang->get('user_management') ?></h5>
                </div>
                <div class="card-body">
                    <p class="card-text"><?= $lang->get('manage_users_and_ranks') ?></p>
                    <a href="/admin/users.php" class="btn btn-primary"><?= $lang->get('manage_users') ?></a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card admin-card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">🛡️ <?= $lang->get('manage_ranks_permissions') ?></h5>
                </div>
                <div class="card-body">
                    <p class="card-text"><?= $lang->get('manage_ranks_description') ?></p>
                    <a href="/admin/ranks.php" class="btn btn-success"><?= $lang->get('go_to') ?></a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card admin-card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">🌀 <?= $lang->get('torrent_management') ?></h5>
                </div>
                <div class="card-body">
                    <p class="card-text"><?= $lang->get('manage_torrents_categories') ?></p>
                    <a href="/admin/torrents.php" class="btn btn-info"><?= $lang->get('manage_torrents') ?></a>
                    <a href="/admin/categories.php" class="btn btn-outline-info mt-2"><?= $lang->get('manage_categories') ?></a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card admin-card h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">💬 <?= $lang->get('forum_management') ?></h5>
                </div>
                <div class="card-body">
                    <p class="card-text"><?= $lang->get('manage_forums_and_topics') ?></p>
                    <a href="/admin/forums.php" class="btn btn-warning"><?= $lang->get('manage_forums') ?></a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card admin-card h-100">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">🧱 <?= $lang->get('block_management') ?></h5>
                </div>
                <div class="card-body">
                    <p class="card-text"><?= $lang->get('manage_blocks_positions') ?></p>
                    <a href="/admin/blocks.php" class="btn btn-danger"><?= $lang->get('manage_blocks') ?></a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card admin-card h-100">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">🌍 <?= $lang->get('translation_management') ?></h5>
                </div>
                <div class="card-body">
                    <p class="card-text"><?= $lang->get('manage_community_translations') ?></p>
                    <a href="/admin/translations.php" class="btn btn-secondary"><?= $lang->get('manage_translations') ?></a>
                    <span class="badge bg-warning text-dark ms-2"><?= $pendingTranslations ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card admin-card h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">📈 <?= $lang->get('tracker_management') ?></h5>
                </div>
                <div class="card-body">
                    <p class="card-text"><?= $lang->get('view_tracker_statistics') ?></p>
                    <a href="/admin/scrape.php" class="btn btn-dark"><?= $lang->get('tracker_statistics') ?></a>
                </div>
            </div>
        </div>

        <!-- ✅ Направен работещ линк към настройките -->
        <div class="col-md-6 mb-4">
            <div class="card admin-card h-100">
                <div class="card-header bg-purple text-white">
                    <h5 class="mb-0">⚙️ <?= $lang->get('system_settings') ?></h5>
                </div>
                <div class="card-body">
                    <p class="card-text"><?= $lang->get('configure_api_keys_and_site_options') ?></p>
                    <a href="/admin/settings.php" class="btn btn-purple"><?= $lang->get('settings') ?></a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>