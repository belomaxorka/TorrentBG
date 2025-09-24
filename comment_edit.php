<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Language.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$lang = new Language($_SESSION['lang'] ?? 'en');

if (!$auth->isLoggedIn()) {
    $_SESSION['error'] = $lang->get('login_to_edit');
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

if ($_POST['comment_id'] ?? false && $_POST['comment'] ?? false) {
    $commentId = (int)$_POST['comment_id'];
    $comment = trim($_POST['comment']);
    $userId = $auth->getUser()['id'];

    if (empty($comment)) {
        $_SESSION['error'] = $lang->get('comment_cannot_be_empty');
    } elseif (strlen($comment) > 2000) {
        $_SESSION['error'] = $lang->get('comment_too_long');
    } else {
        try {
            // Проверка дали потребителят може да редактира (свой или админ/модератор)
            $stmt = $pdo->prepare("SELECT user_id FROM torrent_comments WHERE id = ?");
            $stmt->execute([$commentId]);
            $commentAuthor = $stmt->fetchColumn();

            if ($commentAuthor == $userId || $auth->getRank() >= 5) {
                $stmt = $pdo->prepare("UPDATE torrent_comments SET comment = ?, is_edited = 1 WHERE id = ?");
                $stmt->execute([$comment, $commentId]);
                $_SESSION['success'] = $lang->get('comment_edited');
            } else {
                $_SESSION['error'] = $lang->get('no_permission');
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $lang->get('comment_edit_failed');
        }
    }
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;