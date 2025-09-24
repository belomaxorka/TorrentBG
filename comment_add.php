<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Language.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$lang = new Language($_SESSION['lang'] ?? 'en');

if (!$auth->isLoggedIn()) {
    $_SESSION['error'] = $lang->get('login_to_comment');
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

if ($_POST['torrent_id'] ?? false && $_POST['comment'] ?? false) {
    $torrentId = (int)$_POST['torrent_id'];
    $comment = trim($_POST['comment']);

    if (empty($comment)) {
        $_SESSION['error'] = $lang->get('comment_cannot_be_empty');
    } elseif (strlen($comment) > 2000) {
        $_SESSION['error'] = $lang->get('comment_too_long');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO torrent_comments (torrent_id, user_id, comment) VALUES (?, ?, ?)");
            $stmt->execute([$torrentId, $auth->getUser()['id'], $comment]);
            $_SESSION['success'] = $lang->get('comment_added');
        } catch (Exception $e) {
            $_SESSION['error'] = $lang->get('comment_add_failed');
        }
    }
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;