<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Language.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$pdo = Database::getInstance();
$lang = new Language($_GET['lang'] ?? 'en');

// Проверка за ID
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("
        SELECT t.*, c.name as category_name, u.username as uploader_name
        FROM torrents t
        JOIN categories c ON t.category_id = c.id
        JOIN users u ON t.uploader_id = u.id
        WHERE t.id = ?
    ");
    $stmt->execute([$id]);
    $torrent = $stmt->fetch();
    
    if (!$torrent) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => $lang->get('torrent_not_found')]);
        exit;
    }
    
    // Форматиране на отговора
    $response = [
        'status' => 'success',
        'data' => [
            'id' => $torrent['id'],
            'name' => $torrent['name'],
            'description' => $torrent['description'],
            'poster' => $torrent['poster'],
            'imdb_link' => $torrent['imdb_link'],
            'youtube_link' => $torrent['youtube_link'],
            'category' => [
                'id' => $torrent['category_id'],
                'name' => $torrent['category_name']
            ],
            'uploader' => [
                'id' => $torrent['uploader_id'],
                'username' => $torrent['uploader_name']
            ],
            'size' => $torrent['size'],
            'files_count' => $torrent['files_count'],
            'seeders' => $torrent['seeders'],
            'leechers' => $torrent['leechers'],
            'completed' => $torrent['completed'],
            'uploaded_at' => $torrent['uploaded_at'],
            'updated_at' => $torrent['updated_at'],
            'download_url' => '/download.php?id=' . $torrent['id']
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Списък с торенти
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

// Филтри
$where = [];
$params = [];

if (isset($_GET['category'])) {
    $where[] = 't.category_id = ?';
    $params[] = (int)$_GET['category'];
}

if (isset($_GET['search'])) {
    $where[] = '(t.name LIKE ? OR t.description LIKE ?)';
    $searchTerm = '%' . $_GET['search'] . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT t.*, c.name as category_name, u.username as uploader_name
    FROM torrents t
    JOIN categories c ON t.category_id = c.id
    JOIN users u ON t.uploader_id = u.id
    $whereClause
    ORDER BY t.uploaded_at DESC
    LIMIT ? OFFSET ?
");
$params[] = $limit;
$params[] = $offset;
$stmt->execute($params);
$torrents = $stmt->fetchAll();

// Общ брой
$stmt = $pdo->prepare("SELECT COUNT(*) FROM torrents t $whereClause");
$stmt->execute(array_slice($params, 0, -2)); // Без limit и offset
$total = $stmt->fetchColumn();

$response = [
    'status' => 'success',
    'data' => [
        'torrents' => array_map(function($t) {
            return [
                'id' => $t['id'],
                'name' => $t['name'],
                'category' => [
                    'id' => $t['category_id'],
                    'name' => $t['category_name']
                ],
                'uploader' => [
                    'id' => $t['uploader_id'],
                    'username' => $t['uploader_name']
                ],
                'size' => $t['size'],
                'seeders' => $t['seeders'],
                'leechers' => $t['leechers'],
                'uploaded_at' => $t['uploaded_at'],
                'download_url' => '/download.php?id=' . $t['id']
            ];
        }, $torrents),
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => ceil($total / $limit)
        ]
    ]
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);