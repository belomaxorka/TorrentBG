<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Language.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$lang = new Language($_SESSION['lang'] ?? 'en');

if (!$auth->isLoggedIn()) {
    $_SESSION['error'] = $lang->get('login_to_vote');
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

if ($_POST['poll_id'] ?? false && $_POST['option_id'] ?? false) {
    $pollId = (int)$_POST['poll_id'];
    $optionId = (int)$_POST['option_id'];
    $userId = $auth->getUser()['id'];

    try {
        $pdo->beginTransaction();

        // Проверка дали анкетата е активна
        $stmt = $pdo->prepare("SELECT is_active FROM polls WHERE id = ?");
        $stmt->execute([$pollId]);
        $active = $stmt->fetchColumn();
        if (!$active) {
            throw new Exception($lang->get('poll_not_active'));
        }

        // Проверка дали опцията принадлежи на анкетата
        $stmt = $pdo->prepare("SELECT id FROM poll_options WHERE id = ? AND poll_id = ?");
        $stmt->execute([$optionId, $pollId]);
        if (!$stmt->fetch()) {
            throw new Exception($lang->get('invalid_option'));
        }

        // Проверка дали вече е гласувал
        $stmt = $pdo->prepare("SELECT id FROM poll_votes WHERE poll_id = ? AND user_id = ?");
        $stmt->execute([$pollId, $userId]);
        if ($stmt->fetch()) {
            throw new Exception($lang->get('already_voted'));
        }

        // Записваме гласа
        $stmt = $pdo->prepare("INSERT INTO poll_votes (poll_id, user_id, option_id) VALUES (?, ?, ?)");
        $stmt->execute([$pollId, $userId, $optionId]);

        // Увеличаваме брояча на опцията
        $stmt = $pdo->prepare("UPDATE poll_options SET votes = votes + 1 WHERE id = ?");
        $stmt->execute([$optionId]);

        $pdo->commit();
        $_SESSION['success'] = $lang->get('vote_registered');

    } catch (Exception $e) {
        $pdo->rollback();
        $_SESSION['error'] = $e->getMessage();
    }
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;