<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Language.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$lang = new Language($_SESSION['lang'] ?? 'en');

if (!$auth->isLoggedIn()) {
    $_SESSION['error'] = $lang->get('login_to_post');
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

if ($_POST['topic_id'] ?? false && $_POST['content'] ?? false) {
    $topicId = (int)$_POST['topic_id'];
    $content = trim($_POST['content']);
    $userId = $auth->getUser()['id'];

    if (empty($content)) {
        $_SESSION['error'] = $lang->get('post_cannot_be_empty');
    } elseif (strlen($content) > 5000) {
        $_SESSION['error'] = $lang->get('post_too_long');
    } else {
        try {
            $pdo->beginTransaction();

            // Проверка дали темата е заключена
            $stmt = $pdo->prepare("SELECT is_locked, forum_id FROM forum_topics WHERE id = ?");
            $stmt->execute([$topicId]);
            $topic = $stmt->fetch();
            if ($topic['is_locked']) {
                throw new Exception($lang->get('topic_locked'));
            }

            // Вмъкваме мнението
            $stmt = $pdo->prepare("INSERT INTO forum_posts (topic_id, user_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$topicId, $userId, $content]);
            $postId = $pdo->lastInsertId();

            // Обновяваме темата
            $stmt = $pdo->prepare("
                UPDATE forum_topics 
                SET replies = replies + 1, 
                    updated_at = NOW(), 
                    last_post_id = ? 
                WHERE id = ?
            ");
            $stmt->execute([$postId, $topicId]);

            // Обновяваме форума
            $stmt = $pdo->prepare("
                UPDATE forums 
                SET posts_count = posts_count + 1,
                    last_post_id = ?
                WHERE id = ?
            ");
            $stmt->execute([$postId, $topic['forum_id']]);

            // Създаваме известие за автора на темата (ако не е текущия потребител)
            if ($topicId != $userId) {
                $stmt = $pdo->prepare("SELECT user_id FROM forum_topics WHERE id = ?");
                $stmt->execute([$topicId]);
                $topicAuthor = $stmt->fetchColumn();
                
                if ($topicAuthor != $userId) {
                    $stmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, type, title, message, url) 
                        VALUES (?, 'forum_reply', ?, ?, ?)
                    ");
                    $stmt->execute([
                        $topicAuthor,
                        $lang->get('new_reply_in_your_topic'),
                        $lang->get('user_replied_to_your_topic', htmlspecialchars($auth->getUser()['username']), htmlspecialchars($topic['title'])),
                        '/forum_topic.php?id=' . $topicId
                    ]);
                    
                    // Обновяваме брояча на непрочетени известия
                    $pdo->prepare("UPDATE users SET unread_notifications = unread_notifications + 1 WHERE id = ?")->execute([$topicAuthor]);
                }
            }

            $pdo->commit();
            $_SESSION['success'] = $lang->get('post_added');

        } catch (Exception $e) {
            $pdo->rollback();
            $_SESSION['error'] = $e->getMessage();
        }
    }
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;