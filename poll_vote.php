<?php
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Language.php';

$pdo = Database::getInstance();
$auth = new Auth($pdo);
$lang = new Language($_SESSION['lang'] ?? 'en');

// Проверка за логнат потребител
if (!$auth->isLoggedIn()) {
    $_SESSION['error'] = $lang->get('login_to_vote');
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '/'));
    exit;
}

$userId = $auth->getUser()['id'];

// Проверка за налични данни
if (!isset($_POST['poll_id']) || !isset($_POST['option_id'])) {
    $_SESSION['error'] = $lang->get('invalid_vote_data');
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '/'));
    exit;
}

$pollId = (int)$_POST['poll_id'];
$optionId = (int)$_POST['option_id'];

// Валидиране на стойностите
if ($pollId <= 0 || $optionId <= 0) {
    $_SESSION['error'] = $lang->get('invalid_vote_data');
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '/'));
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Проверка дали анкетата съществува и е активна
    $stmt = $pdo->prepare("SELECT is_active FROM polls WHERE id = ?");
    $stmt->execute([$pollId]);
    $isActive = $stmt->fetchColumn();
    if (!$isActive) {
        throw new Exception($lang->get('poll_not_active'));
    }

    // 2. Проверка дали опцията принадлежи на анкетата
    $stmt = $pdo->prepare("SELECT id FROM poll_options WHERE id = ? AND poll_id = ?");
    $stmt->execute([$optionId, $pollId]);
    if (!$stmt->fetch()) {
        throw new Exception($lang->get('invalid_option'));
    }

    // 3. Проверка дали вече е гласувал — използвам SELECT 1 (без нужда от колона 'id')
    $stmt = $pdo->prepare("SELECT 1 FROM poll_votes WHERE poll_id = ? AND user_id = ?");
    $stmt->execute([$pollId, $userId]);
    if ($stmt->fetch()) {
        throw new Exception($lang->get('already_voted'));
    }

    // 4. Запис на гласа
    $stmt = $pdo->prepare("INSERT INTO poll_votes (poll_id, user_id, option_id) VALUES (?, ?, ?)");
    $stmt->execute([$pollId, $userId, $optionId]);

    // 5. Увеличаване на брояча на гласовете
    $stmt = $pdo->prepare("UPDATE poll_options SET votes = votes + 1 WHERE id = ?");
    $stmt->execute([$optionId]);

    $pdo->commit();
    $_SESSION['success'] = $lang->get('vote_registered');

} catch (Exception $e) {
    $pdo->rollback();
    $_SESSION['error'] = $e->getMessage();
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '/'));
exit;