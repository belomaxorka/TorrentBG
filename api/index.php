<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$response = [
    'status' => 'success',
    'message' => 'Torrent Tracker API',
    'version' => '1.0',
    'endpoints' => [
        'GET /api/torrents' => 'Get list of torrents',
        'GET /api/torrents/{id}' => 'Get torrent by ID',
        'GET /api/users' => 'Get list of users',
        'GET /api/users/{id}' => 'Get user by ID',
        'GET /api/statistics' => 'Get site statistics',
        'GET /api/categories' => 'Get list of categories'
    ]
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);