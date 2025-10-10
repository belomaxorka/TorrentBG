<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Language.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$lang = new Language($_SESSION['lang'] ?? 'en');

if ($auth->getRank() < 6) { // Only Owner
    die($lang->get('no_permission'));
}

$message = '';

// Обработка на форми
if ($_POST['action'] ?? false) {
    if ($_POST['action'] === 'update_rank') {
        $userId = (int)$_POST['user_id'];
        $newRank = (int)$_POST['rank'];
        
        if ($newRank < 1 || $newRank > 6) {
            $message = '<div class="alert alert-danger">' . $lang->get('invalid_rank') . '</div>';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET rank = ? WHERE id = ?");
            if ($stmt->execute([$newRank, $userId])) {
                $message = '<div class="alert alert-success">' . $lang->get('user_rank_updated') . '</div>';
            } else {
                $message = '<div class="alert alert-danger">' . $lang->get('user_rank_update_failed') . '</div>';
            }
        }
    }
    
    if ($_POST['action'] === 'delete_user') {
        $userId = (int)$_POST['user_id'];
        if ($userId == $auth->getUser()['id']) {
            $message = '<div class="alert alert-danger">' . $lang->get('cannot_delete_yourself') . '</div>';
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$userId])) {
                $message = '<div class="alert alert-success">' . $lang->get('user_deleted') . '</div>';
            } else {
                $message = '<div class="alert alert-danger">' . $lang->get('user_delete_failed') . '</div>';
            }
        }
    }
}

// ✅ Използваме backticks и подготвена заявка за максимална съвместимост
try {
    $sql = "SELECT `id`, `username`, `email`, `rank`, `created_at`, `last_login` FROM `users` ORDER BY `created_at` DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<h3>Грешка при извличане на потребителите:</h3><pre>" . htmlspecialchars($e->getMessage()) . "</pre>");
}

require_once __DIR__ . '/../templates/header.php';
?>

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
    <h2><?= $lang->get('manage_users') ?></h2>
    <?= $message ?>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><?= $lang->get('users_list') ?></h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><?= $lang->get('username') ?></th>
                                    <th><?= $lang->get('email') ?></th>
                                    <th><?= $lang->get('rank') ?></th>
                                    <th><?= $lang->get('created_at') ?></th>
                                    <th><?= $lang->get('last_login') ?></th>
                                    <th><?= $lang->get('actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['username']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="update_rank">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <select name="rank" class="form-select form-select-sm" onchange="this.form.submit()">
                                                    <option value="1" <?= $user['rank'] == 1 ? 'selected' : '' ?>><?= $lang->get('guest') ?></option>
                                                    <option value="2" <?= $user['rank'] == 2 ? 'selected' : '' ?>><?= $lang->get('user') ?></option>
                                                    <option value="3" <?= $user['rank'] == 3 ? 'selected' : '' ?>><?= $lang->get('uploader') ?></option>
                                                    <option value="4" <?= $user['rank'] == 4 ? 'selected' : '' ?>><?= $lang->get('validator') ?></option>
                                                    <option value="5" <?= $user['rank'] == 5 ? 'selected' : '' ?>><?= $lang->get('moderator') ?></option>
                                                    <option value="6" <?= $user['rank'] == 6 ? 'selected' : '' ?>><?= $lang->get('owner') ?></option>
                                                </select>
                                            </form>
                                        </td>
                                        <td><?= $user['created_at'] ? date('Y-m-d H:i', strtotime($user['created_at'])) : $lang->get('never') ?></td>
                                        <td><?= $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : $lang->get('never') ?></td>
                                        <td>
                                            <?php if ($user['id'] != $auth->getUser()['id']): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('<?= $lang->get('confirm_delete_user') ?>')">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">🗑️</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>