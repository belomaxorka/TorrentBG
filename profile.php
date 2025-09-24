<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Language.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$lang = new Language($_SESSION['lang'] ?? 'en');

if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$user = $auth->getUser();
$message = '';

// Обработка на форми
if ($_POST['action'] ?? false) {
    if ($_POST['action'] === 'update_profile') {
        $email = $_POST['email'] ?? '';
        $language = $_POST['language'] ?? 'en';
        $style = $_POST['style'] ?? 'light';

        if (empty($email)) {
            $message = '<div class="alert alert-danger">' . $lang->get('email_required') . '</div>';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET email = ?, language = ?, style = ? WHERE id = ?");
                $stmt->execute([$email, $language, $style, $user['id']]);
                
                // Обновяваме сесията
                $_SESSION['lang'] = $language;
                $_SESSION['style'] = $style;
                $user['email'] = $email;
                $user['language'] = $language;
                $user['style'] = $style;
                
                $message = '<div class="alert alert-success">' . $lang->get('profile_updated') . '</div>';
            } catch (Exception $e) {
                $message = '<div class="alert alert-danger">' . $lang->get('profile_update_failed') . '</div>';
            }
        }
    }

    if ($_POST['action'] === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $message = '<div class="alert alert-danger">' . $lang->get('fill_all_fields') . '</div>';
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $message = '<div class="alert alert-danger">' . $lang->get('current_password_incorrect') . '</div>';
        } elseif ($newPassword !== $confirmPassword) {
            $message = '<div class="alert alert-danger">' . $lang->get('passwords_dont_match') . '</div>';
        } elseif (strlen($newPassword) < 6) {
            $message = '<div class="alert alert-danger">' . $lang->get('password_too_short') . '</div>';
        } else {
            try {
                $hashedPass = password_hash($newPassword, PASSWORD_ARGON2ID);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPass, $user['id']]);
                $message = '<div class="alert alert-success">' . $lang->get('password_changed') . '</div>';
            } catch (Exception $e) {
                $message = '<div class="alert alert-danger">' . $lang->get('password_change_failed') . '</div>';
            }
        }
    }
}

// Статистики
$stmt = $pdo->prepare("SELECT COUNT(*) FROM torrents WHERE uploader_id = ?");
$stmt->execute([$user['id']]);
$uploadedTorrents = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM forum_topics WHERE user_id = ?");
$stmt->execute([$user['id']]);
$createdTopics = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM forum_posts WHERE user_id = ?");
$stmt->execute([$user['id']]);
$postedReplies = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM torrent_comments WHERE user_id = ?");
$stmt->execute([$user['id']]);
$postedComments = $stmt->fetchColumn();

require_once __DIR__ . '/templates/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h4><?= $lang->get('user_profile') ?></h4>
            </div>
            <div class="card-body text-center">
                <img src="https://via.placeholder.com/150" class="rounded-circle mb-3" alt="<?= htmlspecialchars($user['username']) ?>">
                <h5><?= htmlspecialchars($user['username']) ?></h5>
                <span class="badge bg-secondary">
                    <?php
                    $ranks = [1 => $lang->get('guest'), 2 => $lang->get('user'), 3 => $lang->get('uploader'), 4 => $lang->get('validator'), 5 => $lang->get('moderator'), 6 => $lang->get('owner')];
                    echo $ranks[$user['rank']] ?? $lang->get('unknown');
                    ?>
                </span>
                <div class="mt-3">
                    <small class="text-muted"><?= $lang->get('member_since') ?>: <?= date('Y-m-d', strtotime($user['created_at'])) ?></small><br>
                    <small class="text-muted"><?= $lang->get('last_login') ?>: <?= $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : $lang->get('never') ?></small>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5><?= $lang->get('statistics') ?></h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= $lang->get('uploaded_torrents') ?>:</span>
                        <strong><?= $uploadedTorrents ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= $lang->get('created_topics') ?>:</span>
                        <strong><?= $createdTopics ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= $lang->get('posted_replies') ?>:</span>
                        <strong><?= $postedReplies ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= $lang->get('posted_comments') ?>:</span>
                        <strong><?= $postedComments ?></strong>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <?= $message ?>

        <div class="card mb-4">
            <div class="card-header">
                <h4><?= $lang->get('profile_settings') ?></h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="mb-3">
                        <label class="form-label"><?= $lang->get('email') ?> *</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= $lang->get('language') ?></label>
                        <select name="language" class="form-select">
                            <?php foreach (['en', 'bg', 'fr', 'de', 'ru'] as $code): ?>
                                <option value="<?= $code ?>" <?= $user['language'] == $code ? 'selected' : '' ?>><?= strtoupper($code) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= $lang->get('style') ?></label>
                        <select name="style" class="form-select">
                            <option value="light" <?= $user['style'] == 'light' ? 'selected' : '' ?>><?= $lang->get('light') ?></option>
                            <option value="dark" <?= $user['style'] == 'dark' ? 'selected' : '' ?>><?= $lang->get('dark') ?></option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary"><?= $lang->get('save_changes') ?></button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4><?= $lang->get('change_password') ?></h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-3">
                        <label class="form-label"><?= $lang->get('current_password') ?> *</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= $lang->get('new_password') ?> *</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= $lang->get('confirm_password') ?> *</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-warning"><?= $lang->get('change_password') ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>