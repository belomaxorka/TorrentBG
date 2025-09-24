<?php
if (!defined('IN_BLOCK')) die('Direct access not allowed.');

// Обновяваме онлайн статуса на текущия потребител
if ($auth->isLoggedIn()) {
    $stmt = $pdo->prepare("
        INSERT INTO online_users (user_id, last_activity, is_bot) 
        VALUES (?, NOW(), 0)
        ON DUPLICATE KEY UPDATE last_activity = NOW()
    ");
    $stmt->execute([$auth->getUser()['id']]);
}

// Изчистваме неактивни (повече от 5 минути)
$pdo->exec("DELETE FROM online_users WHERE last_activity < NOW() - INTERVAL 5 MINUTE");

// Взимаме онлайн потребителите
$stmt = $pdo->query("
    SELECT ou.user_id, ou.is_bot, u.username 
    FROM online_users ou
    JOIN users u ON ou.user_id = u.id
    WHERE ou.is_bot = 0
    ORDER BY ou.last_activity DESC
");
$users = $stmt->fetchAll();

// Взимаме ботовете
$stmt = $pdo->query("
    SELECT ou.user_id, ou.is_bot, u.username 
    FROM online_users ou
    JOIN users u ON ou.user_id = u.id
    WHERE ou.is_bot = 1
    ORDER BY ou.last_activity DESC
");
$bots = $stmt->fetchAll();
?>

<div class="card mb-4">
    <div class="card-header">
        <?= $lang->get('online_users_and_bots') ?>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($users)): ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($users as $u): ?>
                    <li class="list-group-item">
                        <i class="bi bi-person-circle text-success"></i>
                        <?= htmlspecialchars($u['username']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (!empty($bots)): ?>
            <div class="border-top"></div>
            <ul class="list-group list-group-flush">
                <?php foreach ($bots as $b): ?>
                    <li class="list-group-item">
                        <i class="bi bi-robot text-primary"></i>
                        <?= htmlspecialchars($b['username']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (empty($users) && empty($bots)): ?>
            <div class="p-3 text-center text-muted">
                <?= $lang->get('no_online_users') ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.bi { font-size: 1.2em; margin-right: 5px; }
</style>