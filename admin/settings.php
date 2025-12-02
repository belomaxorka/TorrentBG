<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Language.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$lang = new Language($_SESSION['lang'] ?? 'en');

// Само админи
if (!$auth->isLoggedIn() || $auth->getRank() < 6) {
    header("Location: /login.php");
    exit;
}

$message = '';

// Запазване на настройките
if ($_POST['save'] ?? false) {
    $site_name = trim($_POST['site_name'] ?? '');
    $site_url = trim($_POST['site_url'] ?? '');
    $tracker_announce = trim($_POST['tracker_announce'] ?? '');
    $site_email = trim($_POST['site_email'] ?? '');
    $omdb_api_key = trim($_POST['omdb_api_key'] ?? '');
    $tracker_mode = ($_POST['tracker_mode'] ?? 'private') === 'open' ? 'open' : 'private';

    if (empty($site_name) || empty($site_url) || !filter_var($site_email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert alert-danger">' . $lang->get('settings_validation_error') . '</div>';
    } else {
        $settings = [
            'site_name' => $site_name,
            'site_url' => $site_url,
            'tracker_announce' => $tracker_announce,
            'site_email' => $site_email,
            'omdb_api_key' => $omdb_api_key,
            'tracker_mode' => $tracker_mode,
        ];

        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
            $stmt->execute([$key, $value, $value]);
        }

        $message = '<div class="alert alert-success">' . $lang->get('settings_saved') . '</div>';
    }
}

function getSetting($pdo, $name, $default = '') {
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE name = ?");
    $stmt->execute([$name]);
    return $stmt->fetchColumn() ?: $default;
}

$site_name = getSetting($pdo, 'site_name', 'My Tracker');
$site_url = getSetting($pdo, 'site_url', 'https://example.com');
$tracker_announce = getSetting($pdo, 'tracker_announce', 'udp://tracker.example.com:80/announce');
$site_email = getSetting($pdo, 'site_email', 'admin@example.com');
$omdb_api_key = getSetting($pdo, 'omdb_api_key', '');
$tracker_mode = getSetting($pdo, 'tracker_mode', 'private');
?>

<?php require_once __DIR__ . '/../templates/header.php'; ?>

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

<div class="row justify-content-center">
    <div class="col-md-8">
        <h2><?= $lang->get('tracker_settings') ?></h2>

        <?= $message ?>

        <form method="POST">
            <!-- Име на трекъра -->
            <div class="mb-3">
                <label class="form-label"><?= $lang->get('site_name') ?></label>
                <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($site_name) ?>" required>
            </div>

            <!-- URL на трекъра -->
            <div class="mb-3">
                <label class="form-label"><?= $lang->get('site_url') ?></label>
                <input type="url" name="site_url" class="form-control" value="<?= htmlspecialchars($site_url) ?>" required>
            </div>

            <!-- Анонс URL -->
            <div class="mb-3">
                <label class="form-label"><?= $lang->get('tracker_announce_url') ?></label>
                <input type="text" name="tracker_announce" class="form-control" value="<?= htmlspecialchars($tracker_announce) ?>" placeholder="udp://tracker.yoursite.com:80/announce">
            </div>

            <!-- Tracker Mode -->
            <div class="mb-3">
                <label class="form-label"><?= $lang->get('tracker_mode') ?></label><br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="tracker_mode" id="mode_private" value="private" <?= $tracker_mode === 'private' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="mode_private"><?= $lang->get('tracker_mode_private') ?></label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="tracker_mode" id="mode_open" value="open" <?= $tracker_mode === 'open' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="mode_open"><?= $lang->get('tracker_mode_open') ?></label>
                </div>
                <div class="form-text">
                    <?= $lang->get('tracker_mode_help') ?>
                </div>
            </div>

            <!-- Имейл на трекъра -->
            <div class="mb-3">
                <label class="form-label"><?= $lang->get('site_email') ?></label>
                <input type="email" name="site_email" class="form-control" value="<?= htmlspecialchars($site_email) ?>" required>
            </div>

            <!-- OMDb API Key -->
            <div class="mb-3">
                <label class="form-label"><?= $lang->get('omdb_api_key') ?></label>
                <input type="text" name="omdb_api_key" class="form-control" value="<?= htmlspecialchars($omdb_api_key) ?>" placeholder="<?= $lang->get('example_api_key') ?>">
                <div class="form-text">
                    <?= $lang->get('get_free_key_from') ?> <a href="https://www.omdbapi.com/apikey.aspx" target="_blank">OMDb API</a>.
                    <?= $lang->get('without_key_no_imdb_data') ?>
                </div>
            </div>

            <button type="submit" name="save" value="1" class="btn btn-primary"><?= $lang->get('save_settings') ?></button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>