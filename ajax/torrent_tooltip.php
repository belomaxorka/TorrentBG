<?php
// Забрани достъп без валиден ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    exit;
}

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Language.php';

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

function formatBytes($bytes, $precision = 2) {
    if ($bytes === 0) return '0 Б';
    $units = ['Б', 'КБ', 'МБ', 'ГБ', 'ТБ'];
    $step = 1024;
    $i = 0;
    while ($bytes >= $step && $i < count($units) - 1) {
        $bytes /= $step;
        $i++;
    }
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>

<!-- 💡 Картинката с фиксиран максимален размер -->
<?php if (!empty($torrent['poster'])): ?>
    <img src="/<?= htmlspecialchars($torrent['poster']) ?>" 
         alt="<?= htmlspecialchars($lang->get('poster') ?: 'Постер') ?>"
         style="width: 100%; height: auto; max-height: 250px; object-fit: cover; border-radius: 4px;">
<?php else: ?>
    <div class="placeholder" style="width: 100%; height: 150px; display: flex; align-items: center; justify-content: center; background: #444; border-radius: 4px; color: #aaa; font-size: 13px;">
        <?= htmlspecialchars($lang->get('tooltip_no_poster') ?: 'Няма постер') ?>
    </div>
<?php endif; ?>

<div class="stats" style="margin-top: 8px; font-size: 13px; line-height: 1.5;">
    <div class="seeds">🌱 <?= htmlspecialchars($lang->get('tooltip_seeds') ?: 'Сийдъри') ?>: <?= number_format($torrent['seeders'], 0, '', ' ') ?></div>
    <div class="leechers">🐌 <?= htmlspecialchars($lang->get('tooltip_leechers') ?: 'Лийчъри') ?>: <?= number_format($torrent['leechers'], 0, '', ' ') ?></div>
    <div class="size">💾 <?= htmlspecialchars($lang->get('tooltip_size') ?: 'Размер') ?>: <?= formatBytes($torrent['size']) ?></div>
</div>