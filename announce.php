<?php
declare(strict_types=1);

// announce.php - Основен тракер скрипт
// Този файл трябва да бъде бърз и ефективен - без HTML, само bencode отговори

// Забраняваме показване на грешки в продакшън
error_reporting(0);

require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';

$pdo = Database::getInstance();

// Проверка за необходимите параметри
if (!isset($_GET['info_hash']) || !isset($_GET['peer_id']) || !isset($_GET['port']) || !isset($_GET['uploaded']) || !isset($_GET['downloaded']) || !isset($_GET['left'])) {
    sendError(100, 'Missing required parameters');
}

// Декодиране на параметрите
$infoHash = bin2hex($_GET['info_hash']); // Преобразуваме от binary в hex
$peerId = $_GET['peer_id'];
$port = (int)$_GET['port'];
$uploaded = (int)$_GET['uploaded'];
$downloaded = (int)$_GET['downloaded'];
$left = (int)$_GET['left'];
$ip = $_SERVER['REMOTE_ADDR'];
$event = $_GET['event'] ?? 'empty';
$userId = (int)($_GET['user_id'] ?? 1); // В реална употреба това идва от ключа за качване

// Валидация
if (strlen($infoHash) !== 40 || strlen($peerId) > 40 || $port < 1 || $port > 65535) {
    sendError(101, 'Invalid parameters');
}

// Проверка дали торента съществува
$stmt = $pdo->prepare("SELECT id FROM torrents WHERE info_hash = ?");
$stmt->execute([$infoHash]);
$torrent = $stmt->fetch();

if (!$torrent) {
    sendError(200, 'Torrent not found');
}

$torrentId = $torrent['id'];

// Определяне дали е сидър
$isSeeder = $left === 0;

// Обработка на събитията
if ($event === 'stopped') {
    // Премахваме пиъра
    $stmt = $pdo->prepare("DELETE FROM peers WHERE torrent_id = ? AND peer_id = ?");
    $stmt->execute([$torrentId, $peerId]);
} else {
    // Обновяваме или добавяме пиъра
    $stmt = $pdo->prepare("
        INSERT INTO peers 
        (torrent_id, peer_id, ip, port, uploaded, downloaded, `left`, event, user_id, last_announce, is_seeder)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
        ON DUPLICATE KEY UPDATE
        ip = VALUES(ip),
        port = VALUES(port),
        uploaded = VALUES(uploaded),
        downloaded = VALUES(downloaded),
        `left` = VALUES(`left`),
        event = VALUES(event),
        last_announce = NOW(),
        is_seeder = VALUES(is_seeder)
    ");
    $stmt->execute([$torrentId, $peerId, $ip, $port, $uploaded, $downloaded, $left, $event, $userId, $isSeeder]);
}

// Изчистваме старите пиъри (повече от 30 минути без announce)
$pdo->exec("DELETE FROM peers WHERE last_announce < NOW() - INTERVAL 30 MINUTE");

// Взимаме активните пиъри за този торент
$stmt = $pdo->prepare("
    SELECT ip, port, uploaded, downloaded, `left`, is_seeder
    FROM peers 
    WHERE torrent_id = ? AND last_announce >= NOW() - INTERVAL 30 MINUTE
    LIMIT 50
");
$stmt->execute([$torrentId]);
$peers = $stmt->fetchAll();

// Форматиране на пиърите за отговора
$peerList = [];
foreach ($peers as $peer) {
    $peerList[] = [
        'ip' => $peer['ip'],
        'port' => (int)$peer['port'],
        'uploaded' => (int)$peer['uploaded'],
        'downloaded' => (int)$peer['downloaded'],
        'left' => (int)$peer['left'],
    ];
}

// Изчисляваме статистиките
$seeders = $pdo->prepare("SELECT COUNT(*) FROM peers WHERE torrent_id = ? AND is_seeder = 1 AND last_announce >= NOW() - INTERVAL 30 MINUTE");
$seeders->execute([$torrentId]);
$seederCount = $seeders->fetchColumn();

$leechers = $pdo->prepare("SELECT COUNT(*) FROM peers WHERE torrent_id = ? AND is_seeder = 0 AND last_announce >= NOW() - INTERVAL 30 MINUTE");
$leechers->execute([$torrentId]);
$leecherCount = $leechers->fetchColumn();

// Обновяваме статистиките в таблицата torrents
$pdo->prepare("
    UPDATE torrents 
    SET seeders = ?, leechers = ? 
    WHERE id = ?
")->execute([$seederCount, $leecherCount, $torrentId]);

// Подготвяме отговора
$response = [
    'interval' => 1800, // 30 минути
    'min interval' => 900, // 15 минути
    'complete' => (int)$seederCount,
    'incomplete' => (int)$leecherCount,
    'peers' => $peerList
];

// Изпращаме bencode отговор
header('Content-Type: text/plain');
echo bencode($response);
exit;

// Функции за bencode
function bencode($data) {
    if (is_array($data)) {
        if (isset($data[0])) { // list
            $encoded = 'l';
            foreach ($data as $item) {
                $encoded .= bencode($item);
            }
            return $encoded . 'e';
        } else { // dict
            ksort($data);
            $encoded = 'd';
            foreach ($data as $key => $value) {
                $encoded .= bencode((string)$key) . bencode($value);
            }
            return $encoded . 'e';
        }
    }
    if (is_int($data)) {
        return 'i' . $data . 'e';
    }
    if (is_string($data)) {
        return strlen($data) . ':' . $data;
    }
    return '';
}

function sendError(int $code, string $message) {
    header('Content-Type: text/plain');
    echo bencode(['failure reason' => $message, 'error code' => $code]);
    exit;
}