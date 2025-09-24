<?php
if (!defined('IN_BLOCK')) die('Direct access not allowed.');

$stmt = $pdo->prepare("SELECT id, name, size, seeders, leechers, uploaded_at FROM torrents ORDER BY uploaded_at DESC LIMIT 5");
$stmt->execute();
$torrents = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($torrents)) {
    echo '<div class="alert alert-info">' . htmlspecialchars($lang->get('no_torrents_yet')) . '</div>';
    return;
}
?>
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-arrow-down-circle me-2"></i><?= htmlspecialchars($lang->get('latest_torrents')) ?>
    </div>
    <div class="card-body p-0">
        <ul class="list-group list-group-flush">
            <?php foreach ($torrents as $t): ?>
                <li class="list-group-item">
                    <a href="/torrent.php?id=<?= $t['id'] ?>" class="text-decoration-none">
                        <i class="bi bi-file-earmark-arrow-down me-2 text-primary"></i>
                        <?= htmlspecialchars($t['name']) ?>
                    </a>
                    <div class="small text-muted mt-1">
                        <span class="me-3"><?= formatBytes($t['size']) ?></span>
                        <span class="me-3">S:<?= $t['seeders'] ?></span>
                        <span class="me-3">L:<?= $t['leechers'] ?></span>
                        <span><?= date('d.m.Y', strtotime($t['uploaded_at'])) ?></span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<?php
// Хелпер функция за форматиране на байтове
function formatBytes(int $bytes, int $precision = 2): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>