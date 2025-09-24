<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/Database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$pdo = Database::getInstance();

$totalTorrents = $pdo->query("SELECT COUNT(*) FROM torrents")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeSeeders = $pdo->query("SELECT COUNT(*) FROM peers WHERE is_seeder = 1 AND last_announce >= NOW() - INTERVAL 30 MINUTE")->fetchColumn();
$activeLeechers = $pdo->query("SELECT COUNT(*) FROM peers WHERE is_seeder = 0 AND last_announce >= NOW() - INTERVAL 30 MINUTE")->fetchColumn();
$totalPeers = $activeSeeders + $activeLeechers;

// Торенти по категории
$stmt = $pdo->query("
    SELECT c.name, COUNT(t.id) as count
    FROM categories c
    LEFT JOIN torrents t ON c.id = t.category_id
    GROUP BY c.id, c.name
    ORDER BY count DESC
");
$categoryStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$response = [
    'status' => 'success',
    'data' => [
        'total_torrents' => $totalTorrents,
        'total_users' => $totalUsers,
        'active_seeders' => $activeSeeders,
        'active_leechers' => $activeLeechers,
        'total_peers' => $totalPeers,
        'categories' => $categoryStats,
        'updated_at' => date('Y-m-d H:i:s')
    ]
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);