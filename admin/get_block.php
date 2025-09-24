<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);

if ($auth->getRank() < 6) {
    echo json_encode(['error' => 'No permission']);
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM blocks WHERE id = ?");
$stmt->execute([$id]);
$block = $stmt->fetch();

if (!$block) {
    echo json_encode(['error' => 'Block not found']);
    exit;
}

echo json_encode([
    'id' => $block['id'],
    'name' => $block['name'],
    'title' => $block['title'],
    'position' => $block['position'],
    'order' => $block['order'],
    'is_active' => $block['is_active'],
    'is_locked' => $block['is_locked']
]);