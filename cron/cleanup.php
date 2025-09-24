<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Language.php';

$pdo = Database::getInstance();

// Почистване на неактивни пиъри (повече от 30 минути)
$deletedPeers = $pdo->exec("DELETE FROM peers WHERE last_announce < NOW() - INTERVAL 30 MINUTE");

// Почистване на стари сесии (повече от 7 дни)
$deletedSessions = $pdo->exec("DELETE FROM sessions WHERE last_activity < NOW() - INTERVAL 7 DAY");

// Почистване на стари известия (повече от 30 дни)
$deletedNotifications = $pdo->exec("DELETE FROM notifications WHERE created_at < NOW() - INTERVAL 30 DAY");

// Обновяване на статистиките за всички торенти
$stmt = $pdo->query("SELECT id, info_hash FROM torrents");
$torrents = $stmt->fetchAll();

$updatedTorrents = 0;
foreach ($torrents as $torrent) {
    // Преброяваме активните сидъри и лийчъри
    $seeders = $pdo->prepare("SELECT COUNT(*) FROM peers WHERE torrent_id = ? AND is_seeder = 1 AND last_announce >= NOW() - INTERVAL 30 MINUTE");
    $seeders->execute([$torrent['id']]);
    $seederCount = $seeders->fetchColumn();

    $leechers = $pdo->prepare("SELECT COUNT(*) FROM peers WHERE torrent_id = ? AND is_seeder = 0 AND last_announce >= NOW() - INTERVAL 30 MINUTE");
    $leechers->execute([$torrent['id']]);
    $leecherCount = $leechers->fetchColumn();
    
    // Обновяваме статистиките
    $pdo->prepare("
        UPDATE torrents 
        SET seeders = ?, leechers = ? 
        WHERE id = ?
    ")->execute([$seederCount, $leecherCount, $torrent['id']]);
    
    $updatedTorrents++;
}

// Записваме в лог файл
$logMessage = "[" . date('Y-m-d H:i:s') . "] Cleanup completed: Deleted $deletedPeers peers, $deletedSessions sessions, $deletedNotifications notifications, Updated $updatedTorrents torrents\n";
file_put_contents(__DIR__ . '/logs/cleanup.log', $logMessage, FILE_APPEND);

echo "Cleanup completed successfully!\n";
echo "Deleted peers: $deletedPeers\n";
echo "Deleted sessions: $deletedSessions\n";
echo "Deleted notifications: $deletedNotifications\n";
echo "Updated torrents: $updatedTorrents\n";