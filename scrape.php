<?php
declare(strict_types=1);

// scrape.php - Provides statistics for torrents
error_reporting(0);

require_once __DIR__ . '/includes/Database.php';

$pdo = Database::getInstance();

// Helper function for settings
function getSetting($pdo, $name, $default = '') {
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE name = ?");
    $stmt->execute([$name]);
    return $stmt->fetchColumn() ?: $default;
}

// Check for info_hash
if (!isset($_GET['info_hash'])) {
    sendError(100, 'Missing info_hash parameter');
}

// Process single or array of info_hash
$infoHashes = [];
if (is_array($_GET['info_hash'])) {
    foreach ($_GET['info_hash'] as $hash) {
        if (is_string($hash) && strlen($hash) === 20) {
            $infoHashes[] = bin2hex($hash);
        }
    }
} else {
    if (is_string($_GET['info_hash']) && strlen($_GET['info_hash']) === 20) {
        $infoHashes[] = bin2hex($_GET['info_hash']);
    }
}

// Limit: maximum 74 torrents according to BEP 0003
if (empty($infoHashes) || count($infoHashes) > 74) {
    sendError(101, 'Invalid or too many info_hash values (max 74 allowed)');
}

$trackerMode = getSetting($pdo, 'tracker_mode', 'private');
$response = ['files' => []];

if ($trackerMode === 'private') {
    // Get torrent_id and info_hash for valid torrents
    $placeholders = str_repeat('?,', count($infoHashes) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT id, info_hash FROM torrents WHERE info_hash IN ($placeholders)
    ");
    $stmt->execute($infoHashes);
    $validTorrents = [];
    foreach ($stmt->fetchAll() as $row) {
        $validTorrents[$row['info_hash']] = $row['id'];
    }

    // Collect statistics from peers for all valid info_hash at once
    if (!empty($validTorrents)) {
        $validHashes = array_keys($validTorrents);
        $placeholders2 = str_repeat('?,', count($validHashes) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT 
                info_hash,
                SUM(seeder = 1) AS complete,
                SUM(seeder = 0) AS incomplete
            FROM peers 
            WHERE info_hash IN ($placeholders2) 
              AND last_announce >= NOW() - INTERVAL 30 MINUTE
            GROUP BY info_hash
        ");
        $stmt->execute($validHashes);
        $peerStats = [];
        foreach ($stmt->fetchAll() as $row) {
            $peerStats[$row['info_hash']] = [
                'complete' => (int)$row['complete'],
                'incomplete' => (int)$row['incomplete']
            ];
        }
    }

    // Form response
    foreach ($infoHashes as $hash) {
        if (isset($validTorrents[$hash])) {
            $stats = $peerStats[$hash] ?? ['complete' => 0, 'incomplete' => 0];
            // If you want downloaded from torrents.completed, add it from torrents table
            $response['files'][$hash] = [
                'complete' => $stats['complete'],
                'incomplete' => $stats['incomplete'],
                'downloaded' => 0 // или вземи от torrents.completed
            ];
        } else {
            $response['files'][$hash] = [
                'complete' => 0,
                'incomplete' => 0,
                'downloaded' => 0
            ];
        }
    }
} else {
    // OPEN mode: count directly from peers by info_hash
    $placeholders = str_repeat('?,', count($infoHashes) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT 
            info_hash,
            SUM(seeder = 1) AS complete,
            SUM(seeder = 0) AS incomplete
        FROM peers 
        WHERE info_hash IN ($placeholders) 
          AND last_announce >= NOW() - INTERVAL 30 MINUTE
        GROUP BY info_hash
    ");
    $stmt->execute($infoHashes);
    $peerStats = [];
    foreach ($stmt->fetchAll() as $row) {
        $peerStats[$row['info_hash']] = [
            'complete' => (int)$row['complete'],
            'incomplete' => (int)$row['incomplete']
        ];
    }

    foreach ($infoHashes as $hash) {
        $stats = $peerStats[$hash] ?? ['complete' => 0, 'incomplete' => 0];
        $response['files'][$hash] = [
            'complete' => $stats['complete'],
            'incomplete' => $stats['incomplete'],
            'downloaded' => 0
        ];
    }
}

// Send bencode response
header('Content-Type: text/plain');
echo bencode($response);
exit;

// Functions for bencode
function bencode($data) {
    if (is_array($data)) {
        if (isset($data[0]) || empty($data)) { // list
            $encoded = 'l';
            foreach ($data as $item) {
                $encoded .= bencode($item);
            }
            return $encoded . 'e';
        } else { // dict
            ksort($data, SORT_STRING);
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
    return '0:';
}

function sendError(int $code, string $message) {
    header('Content-Type: text/plain');
    echo bencode(['failure reason' => $message, 'error code' => $code]);
    exit;
}