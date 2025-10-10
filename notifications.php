<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Маркираме всички известия като прочетени
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$auth->getUser()['id']]);
$pdo->prepare("UPDATE users SET unread_notifications = 0 WHERE id = ?")->execute([$auth->getUser()['id']]);

// Взимаме известията
$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$auth->getUser()['id']]);
$notifications = $stmt->fetchAll();

require_once __DIR__ . '/templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center"><?= $lang->get('notifications') ?></h3>
            </div>
            <div class="card-body">
                <?php if (empty($notifications)): ?>
                    <div class="alert alert-info"><?= $lang->get('no_notifications') ?></div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($notifications as $notif): ?>
                            <a href="<?= $notif['url'] ?>" class="list-group-item list-group-item-action <?= $notif['is_read'] ? '' : 'list-group-item-primary' ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1"><?= htmlspecialchars($notif['title']) ?></h5>
                                    <small><?= date('Y-m-d H:i', strtotime($notif['created_at'])) ?></small>
                                </div>
                                <p class="mb-1"><?= htmlspecialchars($notif['message']) ?></p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>