<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Language.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$lang = new Language($_SESSION['lang'] ?? 'en');

if (!$auth->isLoggedIn()) {
    $_SESSION['error'] = $lang->get('login_to_delete');
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

if ($_POST['comment_id'] ?? false) {
    $commentId = (int)$_POST['comment_id'];
    $userId = $auth->getUser()['id'];

    try {
        // Проверка дали потребителят може да изтрие (свой или админ/модератор)
        $stmt = $pdo->prepare("SELECT user_id FROM torrent_comments WHERE id = ?");
        $stmt->execute([$commentId]);
        $commentAuthor = $stmt->fetchColumn();

        if ($commentAuthor == $userId || $auth->getRank() >= 5) {
            $stmt = $pdo->prepare("DELETE FROM torrent_comments WHERE id = ?");
            $stmt->execute([$commentId]);
            $_SESSION['success'] = $lang->get('comment_deleted');
        } else {
            $_SESSION['error'] = $lang->get('no_permission');
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $lang->get('comment_delete_failed');
    }
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;