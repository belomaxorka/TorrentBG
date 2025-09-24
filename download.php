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

$stmt = $pdo->prepare("SELECT * FROM torrents WHERE id = ?");
$stmt->execute([$id]);
$torrent = $stmt->fetch();

if (!$torrent) {
    die($lang->get('torrent_not_found'));
}

// Път към .torrent файла
$torrentFile = 'torrents/' . $torrent['info_hash'] . '.torrent';

if (!file_exists($torrentFile)) {
    die($lang->get('torrent_file_not_found'));
}

// Четем и парсваме .torrent файла
$torrentData = file_get_contents($torrentFile);
$decoded = bdecode($torrentData);

if (!$decoded) {
    die($lang->get('invalid_torrent_file'));
}

// Добавяме announce URL
$announceUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/announce.php';
$decoded['announce'] = $announceUrl;

// Добавяме announce-list (за мулти-тракер поддръжка)
$decoded['announce-list'] = [[$announceUrl]];

// Генерираме нов .torrent файл
$encoded = bencode($decoded);

// Увеличаваме брояча на изтегляния
$pdo->prepare("UPDATE torrents SET completed = completed + 1 WHERE id = ?")->execute([$id]);

// Изпращаме файла
header('Content-Type: application/x-bittorrent');
header('Content-Disposition: attachment; filename="' . urlencode($torrent['name']) . '.torrent"');
header('Content-Length: ' . strlen($encoded));
echo $encoded;
exit;

// Bencode функции
function bdecode($data) {
    if ($data[0] === 'd') return bdecode_dict($data);
    if ($data[0] === 'l') return bdecode_list($data);
    if ($data[0] === 'i') return bdecode_int($data);
    if (is_numeric($data[0])) return bdecode_string($data);
    return null;
}

function bdecode_string(&$data) {
    $colon = strpos($data, ':');
    $len = (int)substr($data, 0, $colon);
    $str = substr($data, $colon + 1, $len);
    $data = substr($data, $colon + 1 + $len);
    return $str;
}

function bdecode_int(&$data) {
    $end = strpos($data, 'e');
    $num = (int)substr($data, 1, $end - 1);
    $data = substr($data, $end + 1);
    return $num;
}

function bdecode_list(&$data) {
    $list = [];
    $data = substr($data, 1); // skip 'l'
    while ($data[0] !== 'e') {
        $list[] = bdecode($data);
    }
    $data = substr($data, 1); // skip 'e'
    return $list;
}

function bdecode_dict(&$data) {
    $dict = [];
    $data = substr($data, 1); // skip 'd'
    while ($data[0] !== 'e') {
        $key = bdecode_string($data);
        $value = bdecode($data);
        $dict[$key] = $value;
    }
    $data = substr($data, 1); // skip 'e'
    return $dict;
}

function bencode($data) {
    if (is_string($data)) {
        return strlen($data) . ':' . $data;
    }
    if (is_int($data)) {
        return 'i' . $data . 'e';
    }
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
                $encoded .= bencode($key) . bencode($value);
            }
            return $encoded . 'e';
        }
    }
    return '';
}