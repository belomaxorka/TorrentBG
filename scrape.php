<?php
declare(strict_types=1);

// scrape.php - Предоставя статистики за торентите
error_reporting(0);

require_once __DIR__ . '/includes/Database.php';

$pdo = Database::getInstance();

// Проверка за info_hash
if (!isset($_GET['info_hash'])) {
    sendError(100, 'Missing info_hash parameter');
}

// Ако има няколко info_hash
$infoHashes = [];
if (is_array($_GET['info_hash'])) {
    foreach ($_GET['info_hash'] as $hash) {
        $infoHashes[] = bin2hex($hash);
    }
} else {
    $infoHashes[] = bin2hex($_GET['info_hash']);
}

// Валидация
foreach ($infoHashes as $hash) {
    if (strlen($hash) !== 40) {
        sendError(101, 'Invalid info_hash');
    }
}

// Подготвяме заявката
$placeholders = str_repeat('?,', count($infoHashes) - 1) . '?';
$stmt = $pdo->prepare("
    SELECT id, info_hash, seeders, leechers, completed
    FROM torrents 
    WHERE info_hash IN ($placeholders)
");
$stmt->execute($infoHashes);
$torrents = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Подготвяме отговора
$response = ['files' => []];
foreach ($infoHashes as $hash) {
    if (isset($torrents[$hash])) {
        $response['files'][$hash] = [
            'complete' => (int)$torrents[$hash]['seeders'],
            'downloaded' => (int)$torrents[$hash]['completed'],
            'incomplete' => (int)$torrents[$hash]['leechers'],
            'name' => '', // Може да се добави име при нужда
        ];
    }
}

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