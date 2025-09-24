<?php
if (!defined('IN_BLOCK')) die('Direct access not allowed.');

if (!$auth->isLoggedIn()) {
    echo '<div class="alert alert-info">' . $lang->get('please_login') . '</div>';
    return;
}

$user = $auth->getUser();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM torrents WHERE uploader_id = ?");
$stmt->execute([$user['id']]);
$uploadedCount = $stmt->fetchColumn();

// Рангове
$ranks = [
    1 => $lang->get('guest'),
    2 => $lang->get('user'),
    3 => $lang->get('uploader'),
    4 => $lang->get('validator'),
    5 => $lang->get('moderator'),
    6 => $lang->get('owner')
];
?>
<div class="card mb-4">
    <div class="card-header">
        <?= $lang->get('user_info') ?>
    </div>
    <div class="card-body">
        <div class="text-center mb-3">
            <img src="https://via.placeholder.com/80" class="rounded-circle mb-2" alt="<?= htmlspecialchars($user['username']) ?>">
            <h5><?= htmlspecialchars($user['username']) ?></h5>
            <span class="badge bg-secondary"><?= $ranks[$user['rank']] ?? $lang->get('unknown') ?></span>
        </div>
        <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between">
                <span><?= $lang->get('uploaded_torrents') ?>:</span>
                <strong><?= $uploadedCount ?></strong>
            </li>
            <li class="list-group-item d-flex justify-content-between">
                <span><?= $lang->get('member_since') ?>:</span>
                <small><?= date('Y-m-d', strtotime($user['created_at'])) ?></small>
            </li>
            <li class="list-group-item d-flex justify-content-between">
                <span><?= $lang->get('last_login') ?>:</span>
                <small><?= $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : $lang->get('never') ?></small>
            </li>
        </ul>
        <div class="mt-3">
            <a href="/profile.php" class="btn btn-sm btn-outline-primary w-100"><?= $lang->get('edit_profile') ?></a>
        </div>
    </div>
</div>