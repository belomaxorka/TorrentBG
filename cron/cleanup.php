<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Language.php';

$pdo = Database::getInstance();

// Clean up inactive peers (older than 30 minutes)
$deletedPeers = $pdo->exec("DELETE FROM peers WHERE last_announce < NOW() - INTERVAL 30 MINUTE");

// Clean up old sessions (older than 7 days)
$deletedSessions = $pdo->exec("DELETE FROM sessions WHERE last_activity < NOW() - INTERVAL 7 DAY");

// Clean up old notifications (older than 30 days)
$deletedNotifications = $pdo->exec("DELETE FROM notifications WHERE created_at < NOW() - INTERVAL 30 DAY");

// Update statistics for all torrents
$stmt = $pdo->query("SELECT id, info_hash FROM torrents");
$torrents = $stmt->fetchAll();

$updatedTorrents = 0;
foreach ($torrents as $torrent) {
    // Count active seeders and leechers
    $seeders = $pdo->prepare("SELECT COUNT(*) FROM peers WHERE torrent_id = ? AND is_seeder = 1 AND last_announce >= NOW() - INTERVAL 30 MINUTE");
    $seeders->execute([$torrent['id']]);
    $seederCount = $seeders->fetchColumn();

    $leechers = $pdo->prepare("SELECT COUNT(*) FROM peers WHERE torrent_id = ? AND is_seeder = 0 AND last_announce >= NOW() - INTERVAL 30 MINUTE");
    $leechers->execute([$torrent['id']]);
    $leecherCount = $leechers->fetchColumn();
    
    // Update statistics
    $pdo->prepare("
        UPDATE torrents 
        SET seeders = ?, leechers = ? 
        WHERE id = ?
    ")->execute([$seederCount, $leecherCount, $torrent['id']]);
    
    $updatedTorrents++;
}

// Write to log file
$logMessage = "[" . date('Y-m-d H:i:s') . "] Cleanup completed: Deleted $deletedPeers peers, $deletedSessions sessions, $deletedNotifications notifications, Updated $updatedTorrents torrents\n";
file_put_contents(__DIR__ . '/logs/cleanup.log', $logMessage, FILE_APPEND);

echo "Cleanup completed successfully!\n";
echo "Deleted peers: $deletedPeers\n";
echo "Deleted sessions: $deletedSessions\n";
echo "Deleted notifications: $deletedNotifications\n";
echo "Updated torrents: $updatedTorrents\n";