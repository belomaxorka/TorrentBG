<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Language.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$lang = new Language($_SESSION['lang'] ?? 'en');

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    die($lang->get('invalid_torrent_id'));
}

$stmt = $pdo->prepare("SELECT id, name, info_hash FROM torrents WHERE id = ?");
$stmt->execute([$id]);
$torrent = $stmt->fetch();

if (!$torrent) {
    die($lang->get('torrent_not_found'));
}

if (empty($torrent['info_hash'])) {
    die("Грешка: info_hash не е зададен за този торент.");
}

$torrentFile = 'torrents/' . $torrent['info_hash'] . '.torrent';

if (!file_exists($torrentFile)) {
    error_log("Файлът не съществува: " . $torrentFile);
    die($lang->get('torrent_file_not_found'));
}

// Увеличаваме брояча на изтегляния
$pdo->prepare("UPDATE torrents SET completed = completed + 1 WHERE id = ?")->execute([$id]);

// Изпращаме файла ДИРЕКТНО — без четене в паметта, без парсване
header('Content-Type: application/x-bittorrent');
header('Content-Disposition: attachment; filename="' . urlencode($torrent['name']) . '.torrent"');
header('Content-Length: ' . filesize($torrentFile));
readfile($torrentFile);
exit;