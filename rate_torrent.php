<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => $lang->get('login_to_rate')]);
    exit;
}

if ($_POST['torrent_id'] ?? false && $_POST['rating'] ?? false) {
    $torrentId = (int)$_POST['torrent_id'];
    $rating = (int)$_POST['rating'];

    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'error' => $lang->get('invalid_rating')]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO torrent_ratings (torrent_id, user_id, rating)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE rating = ?
        ");
        $stmt->execute([$torrentId, $auth->getUser()['id'], $rating, $rating]);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $lang->get('rating_failed')]);
    }
} else {
    echo json_encode(['success' => false, 'error' => $lang->get('invalid_request')]);
}