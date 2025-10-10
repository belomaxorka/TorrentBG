<?php
require_once __DIR__ . '/includes/bootstrap.php';

$error = '';
$success = false;

if ($_POST['register'] ?? false) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    // Валидация
    if (empty($username)) {
        $error = $lang->get('username_required');
    } elseif (empty($email)) {
        $error = $lang->get('email_required');
    } elseif (empty($password)) {
        $error = $lang->get('password_required');
    } elseif ($password !== $password2) {
        $error = $lang->get('passwords_dont_match');
    } elseif (strlen($password) < 6) {
        $error = $lang->get('password_too_short');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = $lang->get('invalid_email_format');
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $error = $lang->get('username_length_invalid');
    } else {
        try {
            // Проверка за съществуващ потребител или имейл
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = $lang->get('user_or_email_exists');
            } else {
                // 🔐 Използваме PASSWORD_DEFAULT за максимална съвместимост
                $hashedPass = password_hash($password, PASSWORD_DEFAULT);
                if ($hashedPass === false) {
                    throw new Exception('Password hashing failed');
                }

                // Вмъкване в базата
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, `rank`, `language`, `style`) VALUES (?, ?, ?, 2, ?, 'light')");
                $stmt->execute([$username, $email, $hashedPass, $_SESSION['lang'] ?? 'en']);

                $success = true;
            }
        } catch (Exception $e) {
            // 🔥 ВРЕМЕННО: показваме грешката за диагностика
            $error = "Грешка: " . htmlspecialchars($e->getMessage());
            // Записваме в лога за по-подробна диагностика
            error_log("REGISTRATION ERROR: " . $e->getMessage());
            
            // 💡 КОГАТО ОТКРИЕШ ПРОБЛЕМА — ВЪРНИ ТОВА:
            // $error = $lang->get('registration_error');
        }
    }
}

require_once __DIR__ . '/templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center"><?= htmlspecialchars($lang->get('register')) ?></h3>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($lang->get('registration_success')) ?><br>
                        <a href="login.php"><?= htmlspecialchars($lang->get('go_to_login')) ?></a>
                    </div>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars($lang->get('username')) ?> *</label>
                            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required minlength="3" maxlength="20">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars($lang->get('email')) ?> *</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars($lang->get('password')) ?> *</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                            <div class="form-text"><?= htmlspecialchars($lang->get('password_help')) ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= htmlspecialchars($lang->get('confirm_password')) ?> *</label>
                            <input type="password" name="password2" class="form-control" required>
                        </div>
                        <input type="hidden" name="register" value="1">
                        <button type="submit" class="btn btn-success w-100"><?= htmlspecialchars($lang->get('register')) ?></button>
                    </form>

                    <div class="text-center mt-3">
                        <a href="login.php"><?= htmlspecialchars($lang->get('already_have_account')) ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>