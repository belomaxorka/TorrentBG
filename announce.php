<?php
require_once __DIR__ . '/includes/Database.php';

$pdo = Database::getInstance();

// Помощна функция за получаване на настройка
function getSetting($pdo, $name, $default = '') {
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE name = ?");
    $stmt->execute([$name]);
    return $stmt->fetchColumn() ?: $default;
}

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
if (strlen($infoHash) !== 20 || strlen($peerId) < 10 || $port < 1 || $port > 65535) {
    die('d14:failure reason18:Invalid info_hash or peer_ide');
}

$infoHashHex = bin2hex($infoHash);
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$seeder = ($left == 0) ? 1 : 0;

// Валидиране на IP
if (!filter_var($ip, FILTER_VALIDATE_IP)) {
    die('d14:failure reason13:Invalid IPe');
}

// Вземи режима на тракера
$trackerMode = getSetting($pdo, 'tracker_mode', 'private');

if ($trackerMode === 'private') {
    // СТАНДАРТЕН PRIVATE РЕЖИМ
    $stmt = $pdo->prepare("SELECT id FROM torrents WHERE info_hash = ?");
    $stmt->execute([$infoHashHex]);
    $torrent = $stmt->fetch();

    if (!$torrent) {
        die('d14:failure reason15:Torrent not founde');
    }

    $torrentId = $torrent['id'];

    // Запис в peers с torrent_id
    $stmt = $pdo->prepare("
        INSERT INTO peers (torrent_id, info_hash, peer_id, ip, port, seeder, uploaded, downloaded, `left`, last_announce)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            seeder = VALUES(seeder),
            uploaded = VALUES(uploaded),
            downloaded = VALUES(downloaded),
            `left` = VALUES(`left`),
            last_announce = NOW()
    ");
    $stmt->execute([$torrentId, $infoHashHex, $peerId, $ip, $port, $seeder, $uploaded, $downloaded, $left]);

} else {
    // OPEN РЕЖИМ — не изисква запис в torrents
    $stmt = $pdo->prepare("
        INSERT INTO peers (torrent_id, info_hash, peer_id, ip, port, seeder, uploaded, downloaded, `left`, last_announce)
        VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            seeder = VALUES(seeder),
            uploaded = VALUES(uploaded),
            downloaded = VALUES(downloaded),
            `left` = VALUES(`left`),
            last_announce = NOW()
    ");
    $stmt->execute([$infoHashHex, $peerId, $ip, $port, $seeder, $uploaded, $downloaded, $left]);
}

// Премахни старите пиъри (> 30 минути)
$pdo->exec("DELETE FROM peers WHERE last_announce < NOW() - INTERVAL 30 MINUTE");

// Вземи списък с други пиъри (макс. 50 общо: 30 IPv4 + 20 IPv6)
if ($trackerMode === 'private') {
    $stmt = $pdo->prepare("
        SELECT ip, port FROM peers 
        WHERE torrent_id = ? AND peer_id != ? 
        ORDER BY RAND() LIMIT 50
    ");
    $stmt->execute([$torrentId, $peerId]);
} else {
    $stmt = $pdo->prepare("
        SELECT ip, port FROM peers 
        WHERE info_hash = ? AND peer_id != ? 
        ORDER BY RAND() LIMIT 50
    ");
    $stmt->execute([$infoHashHex, $peerId]);
}

$peers = $stmt->fetchAll();

$ipv4List = '';
$ipv6List = [];

foreach ($peers as $p) {
    $ipAddr = $p['ip'];
    $portNum = (int)$p['port'];

    if (filter_var($ipAddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $ipv4List .= inet_pton($ipAddr) . pack('n', $portNum);
    } elseif (filter_var($ipAddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        // За IPv6 използваме структура: { "ip": "...", "port": N }
        $ipv6List[] = [
            'ip' => $ipAddr,
            'port' => $portNum
        ];
    }
}

// Компактен отговор за IPv4
$peersPart = strlen($ipv4List) . ':' . $ipv4List;

// Структуриран отговор за IPv6 (ако има)
$peers6Part = '';
if (!empty($ipv6List)) {
    $peers6Encoded = 'l';
    foreach ($ipv6List as $peer) {
        $peers6Encoded .= 'd2:ip' . strlen($peer['ip']) . ':' . $peer['ip'] . '4:porti' . $peer['port'] . 'ee';
    }
    $peers6Encoded .= 'e';
    $peers6Part = '6:peers6' . strlen($peers6Encoded) . ':' . $peers6Encoded;
}

$interval = 1800;
$minInterval = 900;

$response = "d8:intervali{$interval}e12:min intervali{$minInterval}e5:peers{$peersPart}{$peers6Part}e";
echo $response;
exit;