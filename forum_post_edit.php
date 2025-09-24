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

if ($_POST['post_id'] ?? false && $_POST['content'] ?? false) {
    $postId = (int)$_POST['post_id'];
    $content = trim($_POST['content']);
    $userId = $auth->getUser()['id'];
    $topicId = (int)$_POST['topic_id'];

    if (empty($content)) {
        $_SESSION['error'] = $lang->get('post_cannot_be_empty');
    } elseif (strlen($content) > 5000) {
        $_SESSION['error'] = $lang->get('post_too_long');
    } else {
        try {
            // Проверка дали потребителят може да редактира (свой или админ/модератор)
            $stmt = $pdo->prepare("SELECT user_id FROM forum_posts WHERE id = ?");
            $stmt->execute([$postId]);
            $postAuthor = $stmt->fetchColumn();

            if ($postAuthor == $userId || $auth->getRank() >= 5) {
                $stmt = $pdo->prepare("UPDATE forum_posts SET content = ?, is_edited = 1, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$content, $postId]);
                
                // Обновяваме updated_at на темата
                $pdo->prepare("UPDATE forum_topics SET updated_at = NOW() WHERE id = ?")->execute([$topicId]);
                
                $_SESSION['success'] = $lang->get('post_edited');
            } else {
                $_SESSION['error'] = $lang->get('no_permission');
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $lang->get('post_edit_failed');
        }
    }
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;