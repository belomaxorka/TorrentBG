<?php
// Забрани достъп без валиден ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    exit;
}

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Language.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = Database::getInstance();
$id = (int)$_GET['id'];

// Вземаме езика от заявката
$langCode = $_GET['lang'] ?? 'en';
$lang = new Language($langCode);

$stmt = $pdo->prepare("SELECT poster, seeders, leechers, size FROM torrents WHERE id = ?");
$stmt->execute([$id]);
$torrent = $stmt->fetch();

if (!$torrent) {
    echo '<div class="placeholder">' . htmlspecialchars($lang->get('tooltip_no_data') ?: 'Няма данни') . '</div>';
    exit;
}
?>

<?php if (!empty($torrent['poster'])): ?>
    <img src="/<?= htmlspecialchars($torrent['poster']) ?>" alt="<?= htmlspecialchars($lang->get('poster')) ?>">
<?php else: ?>
    <div class="placeholder"><?= htmlspecialchars($lang->get('tooltip_no_poster') ?: 'Няма постер') ?></div>
<?php endif; ?>

<div class="stats">
    <div class="seeds">🌱 <?= htmlspecialchars($lang->get('tooltip_seeds') ?: 'Сийдъри') ?>: <?= number_format($torrent['seeders'], 0, '', ' ') ?></div>
    <div class="leechers">🐌 <?= htmlspecialchars($lang->get('tooltip_leechers') ?: 'Лийчъри') ?>: <?= number_format($torrent['leechers'], 0, '', ' ') ?></div>
    <div class="size">💾 <?= htmlspecialchars($lang->get('tooltip_size') ?: 'Размер') ?>: <?= formatBytes($torrent['size']) ?></div>
</div>