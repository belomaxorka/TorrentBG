<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!$auth->isLoggedIn()) {
    $_SESSION['error'] = $lang->get('login_to_delete');
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

if ($_POST['post_id'] ?? false) {
    $postId = (int)$_POST['post_id'];
    $topicId = (int)$_POST['topic_id'];
    $userId = $auth->getUser()['id'];

    try {
        // Проверка дали потребителят може да изтрие (админ/модератор)
        if ($auth->getRank() < 5) {
            $_SESSION['error'] = $lang->get('no_permission');
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        // Проверка дали това е първото мнение в темата
        $stmt = $pdo->prepare("SELECT id FROM forum_posts WHERE topic_id = ? ORDER BY created_at ASC LIMIT 1");
        $stmt->execute([$topicId]);
        $firstPostId = $stmt->fetchColumn();

        if ($postId == $firstPostId) {
            // Изтриваме цялата тема
            $pdo->beginTransaction();
            
            // Взимаме форума
            $stmt = $pdo->prepare("SELECT forum_id FROM forum_topics WHERE id = ?");
            $stmt->execute([$topicId]);
            $forumId = $stmt->fetchColumn();
            
            // Изтриваме всички мнения
            $pdo->prepare("DELETE FROM forum_posts WHERE topic_id = ?")->execute([$topicId]);
            
            // Изтриваме темата
            $pdo->prepare("DELETE FROM forum_topics WHERE id = ?")->execute([$topicId]);
            
            // Обновяваме форума
            $pdo->prepare("UPDATE forums SET topics_count = topics_count - 1 WHERE id = ?")->execute([$forumId]);
            
            $pdo->commit();
            $_SESSION['success'] = $lang->get('topic_deleted');
            header("Location: /forum_view.php?id=" . $forumId);
            exit;
        } else {
            // Изтриваме само това мнение
            $pdo->beginTransaction();
            
            // Взимаме форума
            $stmt = $pdo->prepare("SELECT f.id as forum_id FROM forum_topics ft JOIN forums f ON ft.forum_id = f.id WHERE ft.id = ?");
            $stmt->execute([$topicId]);
            $forumId = $stmt->fetchColumn();
            
            // Изтриваме мнението
            $pdo->prepare("DELETE FROM forum_posts WHERE id = ?")->execute([$postId]);
            
            // Обновяваме темата
            $pdo->prepare("UPDATE forum_topics SET replies = replies - 1 WHERE id = ?")->execute([$topicId]);
            
            // Обновяваме форума
            $pdo->prepare("UPDATE forums SET posts_count = posts_count - 1 WHERE id = ?")->execute([$forumId]);
            
            $pdo->commit();
            $_SESSION['success'] = $lang->get('post_deleted');
        }
    } catch (Exception $e) {
        $pdo->rollback();
        $_SESSION['error'] = $lang->get('post_delete_failed');
    }
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;