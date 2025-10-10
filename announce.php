<?php
require_once __DIR__ . '/includes/Database.php';

$pdo = Database::getInstance();

// Помощна функция за bdecode (ако нямаш)
function safe_bdecode($s, &$pos = 0) {
    if ($pos >= strlen($s)) return null;
    $c = $s[$pos];
    if ($c === 'd') {
        $pos++;
        $r = [];
        while ($pos < strlen($s) && $s[$pos] !== 'e') {
            $k = safe_bdecode($s, $pos);
            if ($k === null) break;
            $v = safe_bdecode($s, $pos);
            if ($v === null) break;
            $r[$k] = $v;
        }
        $pos++;
        return $r;
    } elseif ($c === 'l') {
        $pos++;
        $r = [];
        while ($pos < strlen($s) && $s[$pos] !== 'e') {
            $r[] = safe_bdecode($s, $pos);
        }
        $pos++;
        return $r;
    } elseif ($c === 'i') {
        $pos++;
        $end = strpos($s, 'e', $pos);
        if ($end === false) return null;
        $n = substr($s, $pos, $end - $pos);
        $pos = $end + 1;
        return (int)$n;
    } elseif (is_numeric($c)) {
        $colon = strpos($s, ':', $pos);
        if ($colon === false) return null;
        $len = (int)substr($s, $pos, $colon - $pos);
        $pos = $colon + 1;
        $str = substr($s, $pos, $len);
        $pos += $len;
        return $str;
    }
    return null;
}

// Вземи info_hash от заявката
if (!isset($_GET['info_hash'])) {
    die('d14:failure reason20:Missing info_hashe');
}

$infoHash = $_GET['info_hash'];
$peerId = $_GET['peer_id'] ?? '';
$port = (int)($_GET['port'] ?? 6881);
$uploaded = (int)($_GET['uploaded'] ?? 0);
$downloaded = (int)($_GET['downloaded'] ?? 0);
$left = (int)($_GET['left'] ?? 1);
$event = $_GET['event'] ?? '';

// Валидиране
if (strlen($infoHash) !== 20 || strlen($peerId) < 10) {
    die('d14:failure reason18:Invalid info_hash or peer_ide');
}

// Намери torrent_id по info_hash (хекс версия)
$infoHashHex = bin2hex($infoHash);
$stmt = $pdo->prepare("SELECT id FROM torrents WHERE info_hash = ?");
$stmt->execute([$infoHashHex]);
$torrent = $stmt->fetch();

if (!$torrent) {
    die('d14:failure reason15:Torrent not founde');
}

$torrentId = $torrent['id'];
$seeder = ($left == 0) ? 1 : 0;

// Обнови или добави peer
$stmt = $pdo->prepare("
    INSERT INTO peers (torrent_id, peer_id, ip, port, seeder, uploaded, downloaded, `left`, last_announce)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE
        seeder = VALUES(seeder),
        uploaded = VALUES(uploaded),
        downloaded = VALUES(downloaded),
        `left` = VALUES(`left`),
        last_announce = NOW()
");
$stmt->execute([$torrentId, $peerId, $_SERVER['REMOTE_ADDR'], $port, $seeder, $uploaded, $downloaded, $left]);

// Премахни старите пиъри (> 30 минути)
$pdo->exec("DELETE FROM peers WHERE last_announce < NOW() - INTERVAL 30 MINUTE");

// Вземи списък с други пиъри (макс. 50)
$stmt = $pdo->prepare("
    SELECT ip, port FROM peers 
    WHERE torrent_id = ? AND peer_id != ? 
    ORDER BY RAND() LIMIT 50
");
$stmt->execute([$torrentId, $peerId]);
$peers = $stmt->fetchAll();

// Форматиране на отговора
$peerList = '';
foreach ($peers as $p) {
    $peerList .= inet_pton($p['ip']) . pack('n', $p['port']);
}

$interval = 1800; // 30 минути
$minInterval = 900; // 15 минути

$response = "d8:intervali{$interval}e12:min intervali{$minInterval}e5:peers" . strlen($peerList) . ":{$peerList}e";
echo $response;
exit;