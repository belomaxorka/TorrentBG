<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Language.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$lang = new Language($_SESSION['lang'] ?? 'en');

header('Content-Type: application/json');

if ($_GET['action'] ?? false) {
    if ($_GET['action'] === 'get') {
        define('IN_BLOCK', true);

        ob_start();
        
        $stmt = $pdo->prepare("
            SELECT s.id, s.message, s.created_at, u.username, u.rank, u.id as user_id
            FROM shoutbox s 
            JOIN users u ON s.user_id = u.id 
            ORDER BY s.created_at DESC LIMIT 10
        ");
        $stmt->execute();
        $messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

        if (empty($messages)) {
            echo '<div class="text-muted small">' . htmlspecialchars($lang->get('shoutbox_empty')) . '</div>';
        } else {
            foreach ($messages as $msg) {
                echo '<div class="mb-2 d-flex justify-content-between align-items-start">';
                echo '<div>';
                echo '<strong>' . htmlspecialchars($msg['username']) . ':</strong>';
                echo '<span class="message-content">';
                
                // ✅ Parse smiles
                $message = htmlspecialchars($msg['message']);
                $smileMap = [
                    'smile' => '😊',
                    'wink' => '😉',
                    'grin' => '😀',
                    'tongue' => '😛',
                    'laugh' => '😂',
                    'sad' => '😢',
                    'angry' => '😠',
                    'shock' => '😲',
                    'cool' => '😎',
                    'blush' => '😳'
                ];
                $message = preg_replace_callback('/\[smile=([a-z]+)\]/', function($matches) use ($smileMap) {
                    return $smileMap[$matches[1]] ?? $matches[0];
                }, $message);
                
                echo $message;
                echo '</span>';
                echo '<div class="small text-muted">' . date('H:i', strtotime($msg['created_at'])) . '</div>';
                echo '</div>';
                
                if ($auth->isLoggedIn() && ($auth->getRank() >= 5 || $auth->getUser()['id'] == $msg['user_id'])) {
                    echo '<button class="btn btn-sm btn-outline-danger delete-btn ms-2" data-id="' . $msg['id'] . '" title="' . htmlspecialchars($lang->get('delete_message')) . '">🗑️</button>';
                }
                echo '</div>';
            }
        }

        $html = ob_get_clean();
        echo json_encode(['success' => true, 'html' => $html]);
        exit;
    }

    if ($_GET['action'] === 'post' && $auth->isLoggedIn()) {
        $message = trim($_POST['message'] ?? '');

        if (empty($message)) {
            echo json_encode(['success' => false, 'error' => $lang->get('message_empty')]);
            exit;
        }

        if (strlen($message) > 255) {
            echo json_encode(['success' => false, 'error' => $lang->get('message_too_long')]);
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO shoutbox (user_id, message) VALUES (?, ?)");
            $stmt->execute([$auth->getUser()['id'], $message]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $lang->get('db_error')]);
        }
        exit;
    }

    // 🗑️ Delete message
    if ($_GET['action'] === 'delete' && $auth->isLoggedIn()) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => $lang->get('invalid_id')]);
            exit;
        }

        $stmt = $pdo->prepare("SELECT user_id FROM shoutbox WHERE id = ?");
        $stmt->execute([$id]);
        $msg = $stmt->fetch();
        if (!$msg) {
            echo json_encode(['success' => false, 'error' => $lang->get('message_not_found')]);
            exit;
        }

        if ($auth->getUser()['id'] != $msg['user_id'] && $auth->getRank() < 5) {
            echo json_encode(['success' => false, 'error' => $lang->get('no_permission')]);
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM shoutbox WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $lang->get('db_error')]);
        }
        exit;
    }

    // 🧹 Clear entire history (Owner+ only)
    if ($_GET['action'] === 'clear' && $auth->isLoggedIn() && $auth->getRank() >= 6) {
        try {
            $pdo->exec("DELETE FROM shoutbox");
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $lang->get('db_error')]);
        }
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);